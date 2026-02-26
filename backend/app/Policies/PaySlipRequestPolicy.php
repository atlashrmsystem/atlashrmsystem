<?php

namespace App\Policies;

use App\Models\PaySlipRequest;
use App\Models\User;

class PaySlipRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->employee_id || $user->hasAnyRole(['admin', 'super-admin']);
    }

    public function view(User $user, PaySlipRequest $paySlipRequest): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($user->employee_id === $paySlipRequest->employee_id) {
            return true;
        }

        if ($user->hasAnyRole(['manager', 'supervisor', 'shift-supervisor'])) {
            return in_array($paySlipRequest->store_id, $user->accessibleStoreIds(), true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('request payslip') && (bool) $user->employee_id;
    }

    public function approve(User $user, PaySlipRequest $paySlipRequest): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return $paySlipRequest->workflow_status === PaySlipRequest::WORKFLOW_PENDING_HR;
        }

        if (
            $user->hasAnyRole(['supervisor', 'shift-supervisor'])
            && $paySlipRequest->workflow_status === PaySlipRequest::WORKFLOW_PENDING_SUPERVISOR
        ) {
            return in_array($paySlipRequest->store_id, $user->accessibleStoreIds(), true);
        }

        if (
            $user->hasRole('manager')
            && $paySlipRequest->workflow_status === PaySlipRequest::WORKFLOW_PENDING_MANAGER
        ) {
            return in_array($paySlipRequest->store_id, $user->accessibleStoreIds(), true);
        }

        return false;
    }

    public function reject(User $user, PaySlipRequest $paySlipRequest): bool
    {
        return $this->approve($user, $paySlipRequest);
    }
}
