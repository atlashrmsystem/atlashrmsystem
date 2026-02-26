<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'manager_user_id',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(BrandArea::class);
    }

    public function scopeForManager(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return $query;
        }

        if ($user->hasRole('manager')) {
            return $query->where('manager_user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
