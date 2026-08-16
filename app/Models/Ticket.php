<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const TYPE_LOST_SHIPMENT = 'lost_shipment';

    public const TYPE_DELAY = 'delay';

    public const TYPE_COLLECTION_ERROR = 'collection_error';

    public const TYPE_DRIVER_COMPLAINT = 'driver_complaint';

    public const TYPE_AGENT_COMPLAINT = 'agent_complaint';

    public const TYPE_OTHER = 'other';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_number', 'shipment_id', 'type', 'status', 'priority',
        'created_by', 'assigned_to', 'sla_due_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['sla_due_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }
}
