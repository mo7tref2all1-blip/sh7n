<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_log';

    protected $fillable = ['actor_id', 'actor_role', 'action', 'subject_type', 'subject_id', 'description', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(?User $actor, string $action, string $subjectType, ?int $subjectId, string $description): self
    {
        return static::create([
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->user_type,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
