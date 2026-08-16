<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantUser extends Model
{
    protected $fillable = ['merchant_id', 'user_id', 'role_in_merchant', 'permissions_scope'];

    protected function casts(): array
    {
        return ['permissions_scope' => 'array'];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
