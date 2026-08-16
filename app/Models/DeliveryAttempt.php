<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttempt extends Model
{
    public const RESULT_NO_ANSWER = 'no_answer';

    public const RESULT_POSTPONED = 'postponed';

    public const RESULT_REFUSED_RECEIPT = 'refused_receipt';

    public const RESULT_REFUSED_RECEIPT_FLED = 'refused_receipt_fled';

    public const RESULT_WRONG_ADDRESS = 'wrong_address';

    public const RESULT_REFUSED_PRICE = 'refused_price';

    /** Results that require immediate escalation to ops/customer service. */
    public const ESCALATING_RESULTS = [self::RESULT_REFUSED_RECEIPT_FLED];

    protected $fillable = [
        'shipment_id', 'attempt_number', 'result', 'notes', 'call_screenshot_path',
        'postponed_to_date', 'driver_id', 'gps_lat', 'gps_lng', 'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'postponed_to_date' => 'date',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
            'attempted_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
