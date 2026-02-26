<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->employee_id || $user->hasAnyRole(['admin', 'super-admin']);
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($user->employee_id === $leaveRequest->employee_id) {
            return true;
        }

        if ($user->hasAnyRole(['manager', 'supervisor', 'shift-supervisor'])) {
            return in_array($leaveRequest->store_id, $user->accessibleStoreIds(), true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('request leave') && (bool) $user->employee_id;
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->view($user, $leaveRequest);
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return $leaveRequest->workflow_status === LeaveRequest::WORKFLOW_PENDING_HR;
        }

        if (
            $user->hasAnyRole(['supervisor', 'shift-supervisor'])
            && $leaveRequest->workflow_status === LeaveRequest::WORKFLOW_PENDING_SUPERVISOR
        ) {
            return in_array($leaveRequest->store_id, $user->accessibleStoreIds(), true);
        }

        if (
            $user->hasRole('manager')
            && $leaveRequest->workflow_status === LeaveRequest::WORKFLOW_PENDING_MANAGER
        ) {
            return in_array($leaveRequest->store_id, $user->accessibleStoreIds(), true);
        }

        return false;
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approve($user, $leaveRequest);
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }
}
