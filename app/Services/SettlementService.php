<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\SettlementAttachment;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Merchant dues = total collections that reached the company − total shipping/return
 * fees, i.e. exactly the merchant wallet's running balance (docs/09). A settlement
 * snapshots that balance, and once it is marked paid a matching payout transaction is
 * appended to the ledger so future shipments keep accumulating cleanly on top.
 */
class SettlementService
{
    public function __construct(private readonly WalletService $wallet) {}

    public function createDraft(Merchant $merchant, ?string $periodFrom, ?string $periodTo, User $actor): Settlement
    {
        return DB::transaction(function () use ($merchant, $periodFrom, $periodTo, $actor) {
            $wallet = $merchant->wallet()->lockForUpdate()->firstOrFail();
            $amount = (float) $wallet->current_balance;

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'لا يوجد رصيد مستحق قابل للتسوية لهذا التاجر حاليًا.']);
            }

            $settlement = Settlement::create([
                'settlement_number' => 'STL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'merchant_id' => $merchant->id,
                'total_amount' => $amount,
                'status' => Settlement::STATUS_DRAFT,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'created_by' => $actor->id,
            ]);

            $shipmentIds = Shipment::query()
                ->where('merchant_id', $merchant->id)
                ->where('financial_status', Shipment::FIN_RECEIVED_BY_COMPANY)
                ->pluck('id');

            foreach ($shipmentIds as $shipmentId) {
                $settlement->items()->create(['shipment_id' => $shipmentId, 'amount_included' => 0]);
            }

            ActivityLog::record($actor, 'created', Settlement::class, $settlement->id,
                "إنشاء تسوية {$settlement->settlement_number} للتاجر {$merchant->business_name} بقيمة {$amount} ج.م");

            return $settlement->fresh();
        });
    }

    public function submitForApproval(Settlement $settlement, User $actor): Settlement
    {
        $settlement->update(['status' => Settlement::STATUS_PENDING_APPROVAL]);
        ActivityLog::record($actor, 'updated', Settlement::class, $settlement->id, "إرسال التسوية {$settlement->settlement_number} لاعتماد الإدارة");

        return $settlement->fresh();
    }

    public function approve(Settlement $settlement, User $actor): Settlement
    {
        $settlement->update([
            'status' => Settlement::STATUS_APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        ActivityLog::record($actor, 'approved', Settlement::class, $settlement->id, "اعتماد التسوية {$settlement->settlement_number}");

        return $settlement->fresh();
    }

    /** Accountant attaches transfer proof (image/PDF) and marks the settlement as paid. */
    public function markPaid(Settlement $settlement, User $actor, string $attachmentPath, string $attachmentType = 'transfer_proof'): Settlement
    {
        return DB::transaction(function () use ($settlement, $actor, $attachmentPath, $attachmentType) {
            $settlement->update(['status' => Settlement::STATUS_PAID, 'paid_at' => now()]);

            SettlementAttachment::create([
                'settlement_id' => $settlement->id,
                'file_path' => $attachmentPath,
                'file_type' => $attachmentType,
                'uploaded_by' => $actor->id,
            ]);

            $this->wallet->payout(
                $settlement->merchant,
                (float) $settlement->total_amount,
                $settlement->id,
                "دفع تسوية {$settlement->settlement_number}",
                $actor
            );

            Shipment::query()
                ->whereIn('id', $settlement->items()->pluck('shipment_id'))
                ->update(['financial_status' => Shipment::FIN_SETTLED_TO_MERCHANT]);

            // Every accountant action shows up on the admin activity report (explicit user requirement).
            ActivityLog::record($actor, 'approved', Settlement::class, $settlement->id,
                "تم دفع التسوية {$settlement->settlement_number} للتاجر {$settlement->merchant->business_name} بقيمة {$settlement->total_amount} ج.م وإرفاق إثبات الدفع");

            return $settlement->fresh();
        });
    }
}
