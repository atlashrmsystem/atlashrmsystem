<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandArea extends Model
{
    protected $fillable = [
        'brand_id',
        'name',
        'manager_user_id',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'brand_area_id');
    }

    public function scopeForManager(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return $query;
        }

        if (! $user->hasRole('manager')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($user): void {
            $q->where('manager_user_id', $user->id)
                ->orWhereHas('brand', function (Builder $brandQuery) use ($user): void {
                    $brandQuery->where('manager_user_id', $user->id);
                });
        });
    }
}
