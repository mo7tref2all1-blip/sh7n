<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentCustodyLog extends Model
{
    protected $table = 'shipment_custody_log';

    protected $fillable = [
        'shipment_id', 'from_type', 'from_id', 'to_type', 'to_id',
        'handed_by', 'received_by', 'handed_at', 'received_at', 'status',
    ];

    protected function casts(): array
    {
        return ['handed_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function handedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_by');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
