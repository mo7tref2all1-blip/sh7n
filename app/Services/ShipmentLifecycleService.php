<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentWalletTransaction;
use App\Models\ActivityLog;
use App\Models\DeliveryAttempt;
use App\Models\Merchant;
use App\Models\Shipment;
use App\Models\ShipmentCustodyLog;
use App\Models\ShipmentPod;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShipmentLifecycleService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly BarcodeService $barcode,
        private readonly WalletService $wallet,
        private readonly CashService $cash,
        private readonly AgentWalletService $agentWallet,
    ) {}

    /**
     * @param  array{consignee_name:string, consignee_phone:string, consignee_phone_alt?:string,
     *     governorate_id:int, zone_id?:int, address_text:string, package_description?:string,
     *     weight_kg?:float, service_type?:string, payment_method?:string,
     *     collection_required?:bool, amount_to_collect?:float, reference_number?:string}  $data
     */
    public function createShipment(Merchant $merchant, array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($merchant, $data, $actor) {
            $weight = (float) ($data['weight_kg'] ?? 1);
            $serviceType = $data['service_type'] ?? 'normal';
            $paymentMethod = $data['payment_method'] ?? Shipment::PAYMENT_COD;
            $collectionRequired = $data['collection_required'] ?? ($paymentMethod === Shipment::PAYMENT_COD);

            $prices = $this->pricing->calculate($merchant, (int) $data['governorate_id'], $data['zone_id'] ?? null, $weight, $serviceType);

            $trackingNumber = $this->generateTrackingNumber();

            $shipment = Shipment::create([
                'tracking_number' => $trackingNumber,
                'merchant_id' => $merchant->id,
                'reference_number' => $data['reference_number'] ?? null,
                'consignee_name' => $data['consignee_name'],
                'consignee_phone' => $data['consignee_phone'],
                'consignee_phone_alt' => $data['consignee_phone_alt'] ?? null,
                'governorate_id' => $data['governorate_id'],
                'zone_id' => $data['zone_id'] ?? null,
                'address_text' => $data['address_text'],
                'package_description' => $data['package_description'] ?? null,
                'weight_kg' => $weight,
                'service_type' => $serviceType,
                'payment_method' => $paymentMethod,
                'collection_required' => $collectionRequired,
                'amount_to_collect' => $collectionRequired ? ($data['amount_to_collect'] ?? 0) : 0,
                'shipping_fee' => $prices['shipping_fee'],
                'return_fee' => $prices['return_fee'],
                'shipment_status' => Shipment::STATUS_CREATED,
                'financial_status' => Shipment::FIN_UNCOLLECTED,
                'barcode_value' => $trackingNumber,
                'qr_value' => $trackingNumber,
                'created_by' => $actor?->id,
            ]);

            $this->logStatus($shipment, Shipment::STATUS_CREATED, $actor);

            // Shipping fee is owed by the merchant from the moment the shipment is
            // created, regardless of how the merchant prices shipping to their own
            // customer (e.g. "free shipping" marketing — docs/09 makes this explicit).
            $this->wallet->debit(
                $merchant,
                WalletTransaction::TYPE_DEBIT_SHIPPING_FEE,
                $prices['shipping_fee'],
                $shipment->id,
                "رسوم شحن {$trackingNumber}",
                $actor
            );

            $this->barcode->generate($trackingNumber);

            ActivityLog::record($actor, 'created', Shipment::class, $shipment->id, "تم إنشاء شحنة {$trackingNumber} للتاجر {$merchant->business_name}");

            return $shipment->fresh();
        });
    }

    /** Assign the shipment to a driver OR an agent (mutually exclusive) — docs/09 "assign لمندوب أو لوكيل". */
    public function assign(Shipment $shipment, ?User $driver, ?Agent $agent, User $actor): Shipment
    {
        $shipment->update([
            'assigned_driver_id' => $driver?->id,
            'assigned_agent_id' => $agent?->id,
        ]);

        ActivityLog::record($actor, 'updated', Shipment::class, $shipment->id,
            'تعيين شحنة '.$shipment->tracking_number.' إلى '.($driver ? "المندوب {$driver->name}" : "الوكيل {$agent->name}"));

        return $shipment->fresh();
    }

    /** Driver/branch scans the barcode and confirms physical pickup from the merchant. */
    public function pickup(Shipment $shipment, User $actor, string $custodyType, int $custodyId): Shipment
    {
        return DB::transaction(function () use ($shipment, $actor, $custodyType, $custodyId) {
            $shipment->update([
                'shipment_status' => Shipment::STATUS_PICKED_UP,
                'custody_type' => $custodyType,
                'custody_id' => $custodyId,
            ]);

            ShipmentCustodyLog::create([
                'shipment_id' => $shipment->id,
                'from_type' => null,
                'from_id' => null,
                'to_type' => $custodyType,
                'to_id' => $custodyId,
                'handed_by' => $actor->id,
                'received_by' => $actor->id,
                'handed_at' => now(),
                'received_at' => now(),
                'status' => 'confirmed',
            ]);

            $this->logStatus($shipment, Shipment::STATUS_PICKED_UP, $actor);

            return $shipment->fresh();
        });
    }

    /**
     * Deliver a shipment. Enforces the merchant's POD policy (docs/05 § 5.1.4):
     * GPS + timestamp are always required; photo/signature depend on merchant settings.
     *
     * @param  array{amount_collected?:float, photo_path?:string, signature_path?:string,
     *     notes?:string, gps_lat:float, gps_lng:float}  $data
     */
    public function deliver(Shipment $shipment, User $driver, array $data): Shipment
    {
        if (empty($data['gps_lat']) || empty($data['gps_lng'])) {
            throw ValidationException::withMessages(['gps' => 'الموقع الجغرافي (GPS) إلزامي دائمًا عند التسليم.']);
        }

        $merchant = $shipment->merchant;

        if ($merchant->pod_photo_required === Merchant::POD_REQUIRED && empty($data['photo_path'])) {
            throw ValidationException::withMessages(['photo_path' => 'هذا التاجر يفرض صورة إثبات تسليم إلزامية.']);
        }

        if ($merchant->pod_signature_required === Merchant::POD_REQUIRED && empty($data['signature_path'])) {
            throw ValidationException::withMessages(['signature_path' => 'هذا التاجر يفرض توقيع العميل إلزاميًا.']);
        }

        return DB::transaction(function () use ($shipment, $driver, $data, $merchant) {
            $amountCollected = $shipment->collection_required
                ? (float) ($data['amount_collected'] ?? $shipment->amount_to_collect)
                : 0;

            $shipment->update([
                'shipment_status' => Shipment::STATUS_DELIVERED,
                'delivered_at' => now(),
                'amount_collected' => $amountCollected,
                // "collected" is informational to the merchant; it is NOT yet part of the
                // settleable balance until the cash physically reaches the company
                // (see CashService::confirmHandover / WalletService::credit).
                'financial_status' => $shipment->collection_required ? Shipment::FIN_COLLECTED : Shipment::FIN_UNCOLLECTED,
            ]);

            ShipmentPod::updateOrCreate(
                ['shipment_id' => $shipment->id],
                [
                    'photo_path' => $data['photo_path'] ?? null,
                    'signature_path' => $data['signature_path'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'gps_lat' => $data['gps_lat'],
                    'gps_lng' => $data['gps_lng'],
                    'recorded_at' => now(),
                    'recorded_by' => $driver->id,
                ]
            );

            $this->logStatus($shipment, Shipment::STATUS_DELIVERED, $driver, null, $data['gps_lat'], $data['gps_lng']);

            if ($shipment->collection_required && $amountCollected > 0) {
                $this->cash->recordCollection($shipment, $driver, $amountCollected);
            }

            if ($shipment->assigned_agent_id) {
                $agent = $shipment->assignedAgent;
                $commission = $this->agentWallet->commissionFor($agent, (float) $shipment->shipping_fee);
                if ($commission > 0) {
                    $this->agentWallet->credit($agent, AgentWalletTransaction::TYPE_COMMISSION_EARNED, $commission, $shipment->id,
                        "عمولة تسليم شحنة {$shipment->tracking_number}");
                }
            }

            ActivityLog::record($driver, 'status_changed', Shipment::class, $shipment->id, "تم تسليم شحنة {$shipment->tracking_number}");

            return $shipment->fresh();
        });
    }

    /**
     * Failed delivery attempt — covers every outcome the driver screen offers:
     * no_answer (requires a call screenshot), postponed, refused_receipt,
     * refused_receipt_fled (auto-escalated), wrong_address, refused_price.
     *
     * @param  array{notes?:string, call_screenshot_path?:string, postponed_to_date?:string,
     *     gps_lat?:float, gps_lng?:float}  $data
     */
    public function failAttempt(Shipment $shipment, User $driver, string $result, array $data = []): Shipment
    {
        if ($result === DeliveryAttempt::RESULT_NO_ANSWER && empty($data['call_screenshot_path'])) {
            throw ValidationException::withMessages([
                'call_screenshot_path' => 'إرفاق سكرين شوت لمحاولة الاتصال إلزامي عند اختيار "لا يرد".',
            ]);
        }

        return DB::transaction(function () use ($shipment, $driver, $result, $data) {
            $attemptNumber = $shipment->delivery_attempts_count + 1;

            DeliveryAttempt::create([
                'shipment_id' => $shipment->id,
                'attempt_number' => $attemptNumber,
                'result' => $result,
                'notes' => $data['notes'] ?? null,
                'call_screenshot_path' => $data['call_screenshot_path'] ?? null,
                'postponed_to_date' => $data['postponed_to_date'] ?? null,
                'driver_id' => $driver->id,
                'gps_lat' => $data['gps_lat'] ?? null,
                'gps_lng' => $data['gps_lng'] ?? null,
                'attempted_at' => now(),
            ]);

            $statusMap = [
                DeliveryAttempt::RESULT_NO_ANSWER => Shipment::STATUS_NO_ANSWER,
                DeliveryAttempt::RESULT_POSTPONED => Shipment::STATUS_POSTPONED,
                DeliveryAttempt::RESULT_REFUSED_RECEIPT => Shipment::STATUS_REFUSED_RECEIPT,
                DeliveryAttempt::RESULT_REFUSED_RECEIPT_FLED => Shipment::STATUS_REFUSED_RECEIPT,
                DeliveryAttempt::RESULT_WRONG_ADDRESS => Shipment::STATUS_WRONG_ADDRESS,
                DeliveryAttempt::RESULT_REFUSED_PRICE => Shipment::STATUS_REFUSED_PRICE,
            ];
            $newStatus = $statusMap[$result] ?? Shipment::STATUS_NO_ANSWER;

            $shipment->update([
                'shipment_status' => $newStatus,
                'delivery_attempts_count' => $attemptNumber,
                'scheduled_delivery_date' => $data['postponed_to_date'] ?? $shipment->scheduled_delivery_date,
            ]);

            $this->logStatus($shipment, $newStatus, $driver, $data['notes'] ?? null, $data['gps_lat'] ?? null, $data['gps_lng'] ?? null);

            // "رفض الاستلام وهروب" needs an immediate, unmissable escalation — not just another row in a table.
            if (in_array($result, DeliveryAttempt::ESCALATING_RESULTS, true)) {
                $ticket = Ticket::create([
                    'ticket_number' => 'TCK-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'shipment_id' => $shipment->id,
                    'type' => Ticket::TYPE_OTHER,
                    'status' => Ticket::STATUS_OPEN,
                    'priority' => 'urgent',
                    'created_by' => $driver->id,
                    'sla_due_at' => now()->addHours(2),
                ]);
                $ticket->comments()->create([
                    'user_id' => $driver->id,
                    'comment' => "⚠️ اشتباه هروب بعد رفض استلام الشحنة {$shipment->tracking_number} — يتطلب تحقيقًا فوريًا.",
                ]);
                ActivityLog::record($driver, 'created', Ticket::class, $ticket->id, "تصعيد فوري: رفض استلام وهروب — شحنة {$shipment->tracking_number}");
            }

            // Auto-return once max attempts is reached (docs/05 § 5.1.3).
            $maxAttempts = $shipment->merchant->max_delivery_attempts;
            if ($attemptNumber >= $maxAttempts && in_array($result, [
                DeliveryAttempt::RESULT_NO_ANSWER, DeliveryAttempt::RESULT_WRONG_ADDRESS,
            ], true)) {
                $shipment->update(['shipment_status' => Shipment::STATUS_RETURNED, 'returned_at' => now()]);
                $this->logStatus($shipment, Shipment::STATUS_RETURNED, $driver, 'تجاوز الحد الأقصى لمحاولات التسليم');
            } elseif (in_array($result, [DeliveryAttempt::RESULT_REFUSED_RECEIPT, DeliveryAttempt::RESULT_REFUSED_RECEIPT_FLED], true)) {
                $shipment->update(['shipment_status' => Shipment::STATUS_RETURNED, 'returned_at' => now()]);
                $this->logStatus($shipment, Shipment::STATUS_RETURNED, $driver, 'رفض العميل استلام الشحنة');
            }

            return $shipment->fresh();
        });
    }

    /** Finalize a return back to the merchant, applying the merchant's return-fee policy. */
    public function completeReturnToMerchant(Shipment $shipment, User $actor): Shipment
    {
        return DB::transaction(function () use ($shipment, $actor) {
            $shipment->update(['shipment_status' => Shipment::STATUS_RETURNED_TO_MERCHANT]);
            $this->logStatus($shipment, Shipment::STATUS_RETURNED_TO_MERCHANT, $actor);

            $merchant = $shipment->merchant;
            $liabilityPercent = $merchant->returnLiabilityPercent();

            if ($liabilityPercent > 0 && (float) $shipment->return_fee > 0) {
                $chargeable = round((float) $shipment->return_fee * $liabilityPercent / 100, 2);
                $this->wallet->debit($merchant, WalletTransaction::TYPE_DEBIT_RETURN_FEE, $chargeable, $shipment->id,
                    "رسوم مرتجع شحنة {$shipment->tracking_number} ({$liabilityPercent}% تحمل التاجر)", $actor);
            }

            return $shipment->fresh();
        });
    }

    public function cancel(Shipment $shipment, User $actor): Shipment
    {
        if ($shipment->shipment_status !== Shipment::STATUS_CREATED) {
            throw ValidationException::withMessages(['status' => 'لا يمكن إلغاء الشحنة إلا قبل استلامها من التاجر.']);
        }

        $shipment->update(['shipment_status' => Shipment::STATUS_CANCELLED]);
        $this->logStatus($shipment, Shipment::STATUS_CANCELLED, $actor);

        return $shipment->fresh();
    }

    private function logStatus(Shipment $shipment, string $status, ?User $actor, ?string $reason = null, $gpsLat = null, $gpsLng = null): void
    {
        $shipment->statusHistory()->create([
            'status' => $status,
            'changed_by' => $actor?->id,
            'reason' => $reason,
            'gps_lat' => $gpsLat,
            'gps_lng' => $gpsLng,
            'created_at' => now(),
        ]);
    }

    private function generateTrackingNumber(): string
    {
        return 'SHP-'.now()->format('Y').'-'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }
}
