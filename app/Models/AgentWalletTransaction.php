<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentWalletTransaction extends Model
{
    public $timestamps = false;

    public const TYPE_COMMISSION_EARNED = 'commission_earned';

    public const TYPE_CREDIT_COLLECTION = 'credit_collection';

    public const TYPE_DEBIT_ADJUSTMENT = 'debit_adjustment';

    public const TYPE_SETTLEMENT_PAYOUT = 'settlement_payout';

    protected $fillable = [
        'agent_wallet_id', 'type', 'amount', 'shipment_id', 'agent_settlement_id',
        'balance_after', 'description', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'balance_after' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function agentWallet(): BelongsTo
    {
        return $this->belongsTo(AgentWallet::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
