<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
    ];

    protected $appends = ['role_names', 'permission_names'];

    public function getRoleNamesAttribute()
    {
        return $this->getRoleNames();
    }

    public function getPermissionNamesAttribute()
    {
        return $this->getAllPermissions()->pluck('name')->values();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'user_store')
            ->withPivot(['role_in_store', 'is_primary'])
            ->withTimestamps();
    }

    public function userStores(): HasMany
    {
        return $this->hasMany(UserStore::class);
    }

    public function managedBrands(): HasMany
    {
        return $this->hasMany(Brand::class, 'manager_user_id');
    }

    public function managedBrandAreas(): HasMany
    {
        return $this->hasMany(BrandArea::class, 'manager_user_id');
    }

    public function accessibleStoreIds(): array
    {
        if ($this->hasRole(['super-admin', 'admin', 'sales-team'])) {
            return Store::query()->pluck('id')->all();
        }

        $storeIds = $this->stores()->pluck('stores.id')->all();
        $employeeStoreId = $this->employee?->store_id;

        if ($employeeStoreId) {
            $storeIds[] = $employeeStoreId;
        }

        $managedStoreIds = Store::query()->forManager($this)->pluck('id')->all();
        $supervisedStoreIds = Store::query()->forSupervisor($this)->pluck('id')->all();

        $storeIds = array_merge($storeIds, $managedStoreIds, $supervisedStoreIds);

        return array_values(array_unique($storeIds));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
