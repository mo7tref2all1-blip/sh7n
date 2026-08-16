<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public $timestamps = false;

    public const TYPE_CREDIT_COLLECTION = 'credit_collection';

    public const TYPE_DEBIT_SHIPPING_FEE = 'debit_shipping_fee';

    public const TYPE_DEBIT_RETURN_FEE = 'debit_return_fee';

    public const TYPE_SETTLEMENT_PAYOUT = 'settlement_payout';

    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';

    protected $fillable = [
        'wallet_id', 'type', 'amount', 'shipment_id', 'settlement_id',
        'balance_after', 'description', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'balance_after' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
