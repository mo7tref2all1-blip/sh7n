<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'governorate_id', 'exclusive_governorate_ids',
        'commission_type', 'commission_value', 'status',
    ];

    protected function casts(): array
    {
        return [
            'exclusive_governorate_ids' => 'array',
            'commission_value' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Agent $agent) {
            $agent->wallet()->create(['current_balance' => 0]);
        });
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /** Internal drivers that work for this agent (users with user_type=driver, agent_id=this). */
    public function drivers(): HasMany
    {
        return $this->hasMany(User::class, 'agent_id')->where('user_type', User::TYPE_DRIVER);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_agent_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(AgentWallet::class);
    }

    /** Governorates this agent has exclusive distribution rights in (primary + extras). */
    public function allGovernorateIds(): array
    {
        return array_unique(array_merge([$this->governorate_id], $this->exclusive_governorate_ids ?? []));
    }
}
