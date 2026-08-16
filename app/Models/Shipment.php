<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    // Operational (shipment_status) — full lifecycle, docs/05-Workflows.md § 5.1.1
    public const STATUS_CREATED = 'created';

    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_ARRIVED_BRANCH = 'arrived_branch';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_ARRIVED_DESTINATION_GOV = 'arrived_destination_gov';

    public const STATUS_HANDED_TO_AGENT = 'handed_to_agent';

    public const STATUS_HANDED_TO_DRIVER = 'handed_to_driver';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_NO_ANSWER = 'no_answer';

    public const STATUS_POSTPONED = 'postponed';

    public const STATUS_REFUSED_RECEIPT = 'refused_receipt';

    public const STATUS_REFUSED_PRICE = 'refused_price';

    public const STATUS_WRONG_ADDRESS = 'wrong_address';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_RETURNED_TO_MERCHANT = 'returned_to_merchant';

    public const STATUS_CANCELLED = 'cancelled';

    public const TERMINAL_STATUSES = [
        self::STATUS_DELIVERED, self::STATUS_RETURNED_TO_MERCHANT, self::STATUS_CANCELLED,
    ];

    // Financial status — kept fully independent from the operational status (docs/05 § 5.3).
    // "collected" is informational only: the merchant sees it instantly, but the cash is
    // still a debt on whoever is holding it (driver/agent/branch) until received_by_company.
    public const FIN_UNCOLLECTED = 'uncollected';

    public const FIN_COLLECTED = 'collected';

    public const FIN_RECEIVED_BY_BRANCH = 'received_by_branch';

    public const FIN_RECEIVED_BY_COMPANY = 'received_by_company';

    public const FIN_SETTLED_TO_MERCHANT = 'settled_to_merchant';

    /** Financial states in which the amount is part of the merchant's settleable balance. */
    public const FIN_SETTLEABLE_STATES = [self::FIN_RECEIVED_BY_COMPANY, self::FIN_SETTLED_TO_MERCHANT];

    public const CUSTODY_BRANCH = 'branch';

    public const CUSTODY_DRIVER = 'driver';

    public const CUSTODY_AGENT = 'agent';

    public const CUSTODY_WAREHOUSE = 'warehouse';

    public const PAYMENT_COD = 'cod';

    public const PAYMENT_PREPAID = 'prepaid';

    public const PAYMENT_VISA_ON_DELIVERY = 'visa_on_delivery';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_WALLET = 'wallet_payment';

    protected $fillable = [
        'tracking_number', 'merchant_id', 'reference_number',
        'consignee_name', 'consignee_phone', 'consignee_phone_alt',
        'governorate_id', 'zone_id', 'address_text', 'gps_lat', 'gps_lng',
        'package_description', 'weight_kg', 'service_type',
        'payment_method', 'collection_required', 'amount_to_collect', 'amount_collected',
        'shipping_fee', 'return_fee',
        'shipment_status', 'financial_status',
        'custody_type', 'custody_id', 'current_branch_id',
        'assigned_driver_id', 'assigned_agent_id',
        'delivery_attempts_count', 'scheduled_delivery_date',
        'delivered_at', 'returned_at', 'is_return_trip',
        'barcode_value', 'qr_value', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_required' => 'boolean',
            'is_return_trip' => 'boolean',
            'amount_to_collect' => 'decimal:2',
            'amount_collected' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'return_fee' => 'decimal:2',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
            'scheduled_delivery_date' => 'date',
            'delivered_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function currentBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class)->latest('created_at');
    }

    public function custodyLog(): HasMany
    {
        return $this->hasMany(ShipmentCustodyLog::class);
    }

    public function pod(): HasOne
    {
        return $this->hasOne(ShipmentPod::class);
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->shipment_status, self::TERMINAL_STATUSES, true);
    }

    public function isSettleable(): bool
    {
        return in_array($this->financial_status, self::FIN_SETTLEABLE_STATES, true);
    }

    public function googleMapsUrl(): ?string
    {
        if (! $this->gps_lat || ! $this->gps_lng) {
            return null;
        }

        return "https://www.google.com/maps?q={$this->gps_lat},{$this->gps_lng}";
    }
}
