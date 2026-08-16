<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashHandover extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'handover_number', 'from_type', 'from_id', 'to_type', 'to_id', 'amount', 'status',
        'handed_by', 'received_by', 'handed_at', 'confirmed_at', 'receipt_attachment_path',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'handed_at' => 'datetime', 'confirmed_at' => 'datetime'];
    }

    public function handedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CashLedger::class);
    }
}
