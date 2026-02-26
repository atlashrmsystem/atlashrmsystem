<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'brand_id',
        'brand_area_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function brandArea(): BelongsTo
    {
        return $this->belongsTo(BrandArea::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_store')
            ->withPivot(['role_in_store', 'is_primary'])
            ->withTimestamps();
    }

    public function userStores(): HasMany
    {
        return $this->hasMany(UserStore::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function salesEntries(): HasMany
    {
        return $this->hasMany(SalesEntry::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scopeForManager(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return $query;
        }

        if (! $user->hasRole('manager')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('brand_area_id', BrandArea::query()->forManager($user)->select('id'));
    }

    public function scopeForSupervisor(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return $query;
        }

        return $query->whereHas('userStores', function (Builder $q) use ($user): void {
            $q->where('user_id', $user->id)
                ->whereIn('role_in_store', ['supervisor', 'shift-supervisor', 'shift supervisor', 'store-supervisor', 'store supervisor']);
        });
    }
}
