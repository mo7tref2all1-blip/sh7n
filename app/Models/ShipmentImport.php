<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentImport extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'merchant_id', 'original_filename', 'status', 'total_rows',
        'success_count', 'error_count', 'errors', 'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array', 'completed_at' => 'datetime'];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
