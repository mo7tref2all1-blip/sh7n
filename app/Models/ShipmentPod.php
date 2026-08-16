<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentPod extends Model
{
    protected $table = 'shipment_pod';

    protected $fillable = [
        'shipment_id', 'photo_path', 'signature_path', 'notes',
        'gps_lat', 'gps_lng', 'recorded_at', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['gps_lat' => 'decimal:7', 'gps_lng' => 'decimal:7', 'recorded_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function googleMapsUrl(): string
    {
        return "https://www.google.com/maps?q={$this->gps_lat},{$this->gps_lng}";
    }
}
