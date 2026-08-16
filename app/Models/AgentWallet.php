<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentWallet extends Model
{
    protected $fillable = ['agent_id', 'current_balance'];

    protected function casts(): array
    {
        return ['current_balance' => 'decimal:2'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AgentWalletTransaction::class)->latest('created_at');
    }
}
