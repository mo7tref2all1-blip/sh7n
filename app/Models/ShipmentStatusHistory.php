<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'shipment_status_history';

    protected $fillable = ['shipment_id', 'status', 'changed_by', 'reason', 'gps_lat', 'gps_lng', 'created_at'];

    protected function casts(): array
    {
        return ['gps_lat' => 'decimal:7', 'gps_lng' => 'decimal:7', 'created_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function googleMapsUrl(): ?string
    {
        if (! $this->gps_lat || ! $this->gps_lng) {
            return null;
        }

        return "https://www.google.com/maps?q={$this->gps_lat},{$this->gps_lng}";
    }
}
