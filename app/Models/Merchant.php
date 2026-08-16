<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Merchant extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const POD_REQUIRED = 'required';

    public const POD_OPTIONAL = 'optional';

    public const POD_DISABLED = 'disabled';

    protected $fillable = [
        'business_name', 'owner_name', 'phone', 'email', 'governorate_id',
        'pricing_plan_id', 'return_pricing_plan_id', 'status',
        'pod_photo_required', 'pod_signature_required',
        'free_returns', 'return_discount_percent', 'max_delivery_attempts',
        'api_token', 'notes',
    ];

    protected $hidden = ['api_token'];

    protected function casts(): array
    {
        return [
            'free_returns' => 'boolean',
            'return_discount_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Merchant $merchant) {
            $merchant->api_token ??= Str::random(64);
        });

        static::created(function (Merchant $merchant) {
            $merchant->wallet()->create(['current_balance' => 0]);
        });
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function returnPricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'return_pricing_plan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(MerchantUser::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function customPricing(): HasMany
    {
        return $this->hasMany(MerchantPricing::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    /**
     * "Return charge percent" the merchant is charged, e.g. merchant that keeps
     * 50% liability on returns has return_discount_percent = 50 => pays 50% of the return fee.
     */
    public function returnLiabilityPercent(): float
    {
        if ($this->free_returns) {
            return 0.0;
        }

        return (float) $this->return_discount_percent > 0
            ? (float) $this->return_discount_percent
            : 100.0;
    }
}
