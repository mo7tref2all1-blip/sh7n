<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantPricing extends Model
{
    protected $table = 'merchant_pricing';

    protected $fillable = [
        'merchant_id', 'governorate_id', 'zone_id',
        'weight_from', 'weight_to', 'service_type', 'price', 'return_price',
    ];

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
}
