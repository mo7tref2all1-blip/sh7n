<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'code'];

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
