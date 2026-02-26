<?php

namespace App\Services;

use App\Models\User;

class MobileAccessService
{
    public function isSupervisor(User $user): bool
    {
        return $user->hasAnyRole(['supervisor', 'shift-supervisor']);
    }

    public function isManager(User $user): bool
    {
        return $user->hasRole('manager');
    }

    public function isHr(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    public function accessibleStoreIds(User $user): array
    {
        return $user->accessibleStoreIds();
    }

    public function canAccessStore(User $user, int $storeId): bool
    {
        return in_array($storeId, $this->accessibleStoreIds($user), true);
    }
}
