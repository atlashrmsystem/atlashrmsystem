<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view own schedule')
            || $user->can('manage schedules')
            || $user->hasAnyRole(['admin', 'super-admin', 'manager', 'supervisor', 'shift-supervisor']);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($user->employee_id === $schedule->employee_id) {
            return true;
        }

        return in_array($schedule->store_id, $user->accessibleStoreIds(), true);
    }

    public function create(User $user): bool
    {
        if (! $user->can('manage schedules')) {
            return false;
        }

        return $user->hasAnyRole(['supervisor', 'shift-supervisor', 'admin', 'super-admin']);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        return in_array($schedule->store_id, $user->accessibleStoreIds(), true);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }
}
