<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->employee_id || $user->can('view store attendances');
    }

    public function view(User $user, AttendanceRecord $attendance): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($user->employee_id === $attendance->employee_id) {
            return true;
        }

        return $user->can('view store attendances')
            && in_array($attendance->store_id, $user->accessibleStoreIds(), true);
    }
}
