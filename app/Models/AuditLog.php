<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_log';

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'old_values', 'new_values',
        'reason', 'ip_address', 'user_agent', 'gps_lat', 'gps_lng', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(array $attributes): self
    {
        $attributes['user_id'] ??= auth()->id();
        $attributes['ip_address'] ??= request()?->ip();
        $attributes['user_agent'] ??= substr((string) request()?->userAgent(), 0, 255);
        $attributes['created_at'] ??= now();

        return static::create($attributes);
    }
}
