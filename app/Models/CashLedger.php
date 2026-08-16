<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashLedger extends Model
{
    public $timestamps = false;

    protected $table = 'cash_ledger';

    public const TYPE_COLLECTION = 'collection';

    public const TYPE_HANDOVER_OUT = 'handover_out';

    public const TYPE_HANDOVER_IN = 'handover_in';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'holder_type', 'holder_id', 'entry_type', 'amount',
        'shipment_id', 'cash_handover_id', 'balance_after', 'reason', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'balance_after' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function cashHandover(): BelongsTo
    {
        return $this->belongsTo(CashHandover::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Current cash-in-hand balance for a holder (driver/branch/agent). */
    public static function balanceFor(string $holderType, int $holderId): float
    {
        return (float) static::where('holder_type', $holderType)
            ->where('holder_id', $holderId)
            ->orderByDesc('id')
            ->value('balance_after') ?? 0.0;
    }
}
