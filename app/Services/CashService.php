<?php

namespace App\Services;

use App\Models\CashHandover;
use App\Models\CashLedger;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Manages the physical cash-in-hand chain: driver -> branch -> company (and
 * agent -> company directly). This is the mechanism behind the "collected for
 * the merchant, but still a debt on the driver internally" requirement: a
 * shipment's financial_status only moves past FIN_COLLECTED once a handover
 * that includes it is *confirmed* by the receiving side.
 */
class CashService
{
    public function recordCollection(Shipment $shipment, User $collector, float $amount): CashLedger
    {
        $holderType = $shipment->assigned_agent_id ? Shipment::CUSTODY_AGENT : Shipment::CUSTODY_DRIVER;
        $holderId = $shipment->assigned_agent_id ?? $shipment->assigned_driver_id;

        return $this->appendLedger($holderType, $holderId, CashLedger::TYPE_COLLECTION, $amount, $shipment->id, null, $collector);
    }

    public function initiateHandover(string $fromType, int $fromId, string $toType, ?int $toId, float $amount, User $handedBy): CashHandover
    {
        return DB::transaction(function () use ($fromType, $fromId, $toType, $toId, $amount, $handedBy) {
            $handover = CashHandover::create([
                'handover_number' => 'CH-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'from_type' => $fromType,
                'from_id' => $fromId,
                'to_type' => $toType,
                'to_id' => $toId,
                'amount' => $amount,
                'status' => CashHandover::STATUS_PENDING,
                'handed_by' => $handedBy->id,
                'handed_at' => now(),
            ]);

            $this->appendLedger($fromType, $fromId, CashLedger::TYPE_HANDOVER_OUT, -$amount, null, $handover->id, $handedBy,
                "توريد نقدية رقم {$handover->handover_number} إلى ".($toType === 'company' ? 'الشركة' : $toType));

            return $handover;
        });
    }

    public function confirmHandover(CashHandover $handover, User $confirmedBy, ?string $receiptPath = null): CashHandover
    {
        return DB::transaction(function () use ($handover, $confirmedBy, $receiptPath) {
            $handover->update([
                'status' => CashHandover::STATUS_CONFIRMED,
                'received_by' => $confirmedBy->id,
                'confirmed_at' => now(),
                'receipt_attachment_path' => $receiptPath ?? $handover->receipt_attachment_path,
            ]);

            if ($handover->to_type !== 'company') {
                $this->appendLedger($handover->to_type, $handover->to_id, CashLedger::TYPE_HANDOVER_IN, $handover->amount, null, $handover->id, $confirmedBy);
            }

            $newFinancialStatus = $handover->to_type === 'company'
                ? Shipment::FIN_RECEIVED_BY_COMPANY
                : Shipment::FIN_RECEIVED_BY_BRANCH;

            $expectedCurrentStatus = $handover->to_type === 'company' && $handover->from_type === 'branch'
                ? Shipment::FIN_RECEIVED_BY_BRANCH
                : Shipment::FIN_COLLECTED;

            $this->promoteShipments($handover->from_type, $handover->from_id, $expectedCurrentStatus, $newFinancialStatus, (float) $handover->amount);

            return $handover->fresh();
        });
    }

    /**
     * FIFO-matches shipments currently sitting at $fromStatus for this holder up to
     * $amount and flips them to $toStatus. When the destination is "received_by_company"
     * the merchant's wallet is credited at the same time (docs/09, financial-status split).
     */
    private function promoteShipments(string $holderType, int $holderId, string $fromStatus, string $toStatus, float $amount): void
    {
        $query = Shipment::query()
            ->where('financial_status', $fromStatus)
            ->where('collection_required', true);

        match ($holderType) {
            Shipment::CUSTODY_DRIVER => $query->where('assigned_driver_id', $holderId),
            Shipment::CUSTODY_AGENT => $query->where('assigned_agent_id', $holderId),
            // A branch handing cash to the company is handing over what its own drivers
            // collected — there is no separate "cash belongs to this branch" column, so
            // this is derived through the driver's home branch instead.
            Shipment::CUSTODY_BRANCH => $query->whereHas('assignedDriver', fn ($q) => $q->where('branch_id', $holderId)),
            default => $query->whereRaw('1 = 0'),
        };

        $shipments = $query->oldest('delivered_at')->get();

        $remaining = $amount;
        $walletService = app(WalletService::class);

        foreach ($shipments as $shipment) {
            if ($remaining <= 0) {
                break;
            }

            $shipment->update(['financial_status' => $toStatus]);
            $remaining -= (float) $shipment->amount_collected;

            if ($toStatus === Shipment::FIN_RECEIVED_BY_COMPANY) {
                $walletService->credit(
                    $shipment->merchant,
                    WalletTransaction::TYPE_CREDIT_COLLECTION,
                    (float) $shipment->amount_collected,
                    $shipment->id,
                    "تحصيل شحنة {$shipment->tracking_number} — وصلت الشركة"
                );
            }
        }
    }

    private function appendLedger(string $holderType, int $holderId, string $entryType, float $signedAmount, ?int $shipmentId, ?int $handoverId, User $actor, ?string $reason = null): CashLedger
    {
        $priorBalance = CashLedger::balanceFor($holderType, $holderId);
        $newBalance = $priorBalance + $signedAmount;

        return CashLedger::create([
            'holder_type' => $holderType,
            'holder_id' => $holderId,
            'entry_type' => $entryType,
            'amount' => $signedAmount,
            'shipment_id' => $shipmentId,
            'cash_handover_id' => $handoverId,
            'balance_after' => $newBalance,
            'reason' => $reason,
            'created_by' => $actor->id,
            'created_at' => now(),
        ]);
    }
}
