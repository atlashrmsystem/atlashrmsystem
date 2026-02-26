<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\PaySlipRequest;
use App\Models\SalaryCertificateRequest;
use App\Models\User;
use App\Notifications\InAppStatusNotification;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class InAppNotificationService
{
    public function notifyLeaveUpdate(LeaveRequest $leaveRequest, User $actor): void
    {
        $leaveRequest->loadMissing(['employee.user', 'store']);

        $requester = $leaveRequest->employee?->user;
        $meta = [
            'module' => 'leave_request',
            'request_id' => $leaveRequest->id,
            'workflow_status' => $leaveRequest->workflow_status,
            'status' => $leaveRequest->status,
        ];

        if ($requester) {
            $message = match ($leaveRequest->workflow_status) {
                LeaveRequest::WORKFLOW_PENDING_MANAGER => 'Your leave request was approved by supervisor and is pending manager approval.',
                LeaveRequest::WORKFLOW_PENDING_HR => 'Your leave request was approved by manager and is pending HR approval.',
                LeaveRequest::WORKFLOW_APPROVED => 'Your leave request has been approved.',
                LeaveRequest::WORKFLOW_REJECTED => 'Your leave request has been rejected.',
                default => 'Your leave request status was updated.',
            };

            $this->notifyUsers(collect([$requester]), $actor, 'Leave Request Update', $message, 'leave_request', $meta);
        }

        if ($leaveRequest->workflow_status === LeaveRequest::WORKFLOW_PENDING_MANAGER) {
            $managers = $this->usersForStoreRoles($leaveRequest->store_id, ['manager']);
            $this->notifyUsers(
                $managers,
                $actor,
                'Leave Approval Needed',
                'A leave request is pending manager approval.',
                'approval_queue',
                $meta
            );
        }

        if ($leaveRequest->workflow_status === LeaveRequest::WORKFLOW_PENDING_SUPERVISOR) {
            $supervisors = $this->usersForStoreRoles($leaveRequest->store_id, ['supervisor', 'shift-supervisor']);
            $this->notifyUsers(
                $supervisors,
                $actor,
                'Leave Approval Needed',
                'A leave request is pending supervisor approval.',
                'approval_queue',
                $meta
            );
        }

        if ($leaveRequest->workflow_status === LeaveRequest::WORKFLOW_PENDING_HR) {
            $hrUsers = $this->usersForRoles(['admin', 'super-admin']);
            $this->notifyUsers(
                $hrUsers,
                $actor,
                'Leave Approval Needed',
                'A leave request is pending HR approval.',
                'approval_queue',
                $meta
            );
        }
    }

    public function notifyPaySlipRequestUpdate(PaySlipRequest $paySlipRequest, User $actor): void
    {
        $paySlipRequest->loadMissing(['employee.user', 'store']);

        $requester = $paySlipRequest->employee?->user;
        $meta = [
            'module' => 'pay_slip_request',
            'request_id' => $paySlipRequest->id,
            'workflow_status' => $paySlipRequest->workflow_status,
            'status' => $paySlipRequest->status,
            'month' => $paySlipRequest->month,
        ];

        if ($requester) {
            $message = match ($paySlipRequest->workflow_status) {
                PaySlipRequest::WORKFLOW_PENDING_MANAGER => 'Your payslip request was approved by supervisor and is pending manager approval.',
                PaySlipRequest::WORKFLOW_PENDING_HR => 'Your payslip request was approved by manager and is pending HR approval.',
                PaySlipRequest::WORKFLOW_APPROVED => 'Your payslip request has been approved.',
                PaySlipRequest::WORKFLOW_REJECTED => 'Your payslip request has been rejected.',
                default => 'Your payslip request status was updated.',
            };

            $this->notifyUsers(collect([$requester]), $actor, 'Payslip Request Update', $message, 'pay_slip_request', $meta);
        }

        if ($paySlipRequest->workflow_status === PaySlipRequest::WORKFLOW_PENDING_MANAGER) {
            $managers = $this->usersForStoreRoles($paySlipRequest->store_id, ['manager']);
            $this->notifyUsers(
                $managers,
                $actor,
                'Payslip Approval Needed',
                'A payslip request is pending manager approval.',
                'approval_queue',
                $meta
            );
        }

        if ($paySlipRequest->workflow_status === PaySlipRequest::WORKFLOW_PENDING_SUPERVISOR) {
            $supervisors = $this->usersForStoreRoles($paySlipRequest->store_id, ['supervisor', 'shift-supervisor']);
            $this->notifyUsers(
                $supervisors,
                $actor,
                'Payslip Approval Needed',
                'A payslip request is pending supervisor approval.',
                'approval_queue',
                $meta
            );
        }

        if ($paySlipRequest->workflow_status === PaySlipRequest::WORKFLOW_PENDING_HR) {
            $hrUsers = $this->usersForRoles(['admin', 'super-admin']);
            $this->notifyUsers(
                $hrUsers,
                $actor,
                'Payslip Approval Needed',
                'A payslip request is pending HR approval.',
                'approval_queue',
                $meta
            );
        }
    }

    public function notifySalaryCertificateRequestUpdate(SalaryCertificateRequest $request, User $actor): void
    {
        $request->loadMissing(['employee.user']);

        $requester = $request->employee?->user;
        $meta = [
            'module' => 'salary_certificate_request',
            'request_id' => $request->id,
            'status' => $request->status,
        ];

        if ($requester) {
            $message = match ($request->status) {
                'approved' => 'Your salary certificate request has been approved.',
                'rejected' => 'Your salary certificate request has been rejected.',
                default => 'Your salary certificate request status was updated.',
            };

            $this->notifyUsers(collect([$requester]), $actor, 'Salary Certificate Request Update', $message, 'salary_certificate_request', $meta);
        }

        if ($request->status === 'pending') {
            $hrUsers = $this->usersForRoles(['admin', 'super-admin']);
            $this->notifyUsers(
                $hrUsers,
                $actor,
                'Salary Certificate Approval Needed',
                'A salary certificate request is pending HR approval.',
                'approval_queue',
                $meta
            );
        }
    }

    private function usersForStoreRoles(?int $storeId, array $roles): Collection
    {
        if (! $storeId) {
            return collect();
        }

        $availableRoles = $this->availableRoleNames($roles);
        if (empty($availableRoles)) {
            return collect();
        }

        return User::role($availableRoles)
            ->where(function ($query) use ($storeId) {
                $query->whereHas('stores', function ($q) use ($storeId) {
                    $q->where('stores.id', $storeId);
                })->orWhereHas('employee', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                });
            })
            ->get();
    }

    private function usersForRoles(array $roles): Collection
    {
        $availableRoles = $this->availableRoleNames($roles);
        if (empty($availableRoles)) {
            return collect();
        }

        return User::role($availableRoles)->get();
    }

    private function availableRoleNames(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $roles)
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    private function notifyUsers(Collection $users, User $actor, string $title, string $message, string $category, array $meta = []): void
    {
        $users
            ->filter(fn ($user) => $user && $user->id !== $actor->id)
            ->unique('id')
            ->each(fn (User $user) => $user->notify(new InAppStatusNotification($title, $message, $category, $meta)));
    }
}
