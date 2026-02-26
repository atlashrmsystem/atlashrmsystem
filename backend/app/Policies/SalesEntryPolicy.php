<?php

namespace App\Policies;

use App\Models\SalesEntry;
use App\Models\User;

class SalesEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view store sales') || $user->can('view store reports') || $user->can('enter sales');
    }

    public function view(User $user, SalesEntry $salesEntry): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($user->employee_id === $salesEntry->employee_id) {
            return true;
        }

        return in_array($salesEntry->store_id, $user->accessibleStoreIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->can('enter sales') && (bool) $user->employee_id;
    }
}
