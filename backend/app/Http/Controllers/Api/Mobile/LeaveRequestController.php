<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ApproveLeaveRequest;
use App\Http\Requests\Mobile\ListLeaveRequestsRequest;
use App\Http\Requests\Mobile\RejectLeaveRequest;
use App\Http\Requests\Mobile\StoreMobileLeaveRequest;
use App\Http\Resources\Mobile\LeaveRequestResource;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\InAppNotificationService;
use App\Services\LeaveService;
use App\Services\MobileAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveService $leaveService,
        private readonly MobileAccessService $mobileAccess,
        private readonly InAppNotificationService $inAppNotifications
    ) {}

    public function index(ListLeaveRequestsRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        $query = LeaveRequest::query()->with(['type', 'employee', 'store'])->latest();
        $mine = filter_var($filters['mine'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isSupervisor = $this->mobileAccess->isSupervisor($user);
        $isManager = $this->mobileAccess->isManager($user);

        if ($mine) {
            if (! $user->employee_id) {
                return LeaveRequestResource::collection(collect());
            }
            $query->where('employee_id', $user->employee_id);
        } elseif ($this->mobileAccess->isHr($user)) {
            $query->when(
                empty($filters['workflow_status']),
                fn ($q) => $q->where('workflow_status', LeaveRequest::WORKFLOW_PENDING_HR)
            );
        } elseif ($isSupervisor || $isManager) {
            $query->whereIn('store_id', $this->mobileAccess->accessibleStoreIds($user));
            if (empty($filters['workflow_status'])) {
                if ($isSupervisor && $isManager) {
                    $query->whereIn('workflow_status', [
                        LeaveRequest::WORKFLOW_PENDING_SUPERVISOR,
                        LeaveRequest::WORKFLOW_PENDING_MANAGER,
                    ]);
                } elseif ($isSupervisor) {
                    $query->where('workflow_status', LeaveRequest::WORKFLOW_PENDING_SUPERVISOR);
                } else {
                    $query->where('workflow_status', LeaveRequest::WORKFLOW_PENDING_MANAGER);
                }
            }
        } else {
            if (! $user->employee_id) {
                return LeaveRequestResource::collection(collect());
            }
            $query->where('employee_id', $user->employee_id);
        }

        if (! empty($filters['workflow_status'])) {
            $query->where('workflow_status', $filters['workflow_status']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        return LeaveRequestResource::collection($query->paginate(20));
    }

    public function show(Request $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorize('view', $leaveRequest);

        return new LeaveRequestResource($leaveRequest->load(['type', 'employee', 'store']));
    }

    public function store(StoreMobileLeaveRequest $request)
    {
        $this->authorize('create', LeaveRequest::class);

        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(404, 'Employee profile not found.');
        }

        $payload = $request->validated();
        if ($request->hasFile('attachment')) {
            $payload['attachment'] = $request->file('attachment');
        }

        $leaveRequest = $this->leaveService->requestLeave($employee, $payload);
        $initialWorkflow = $this->mobileAccess->isSupervisor($user)
            ? LeaveRequest::WORKFLOW_PENDING_MANAGER
            : LeaveRequest::WORKFLOW_PENDING_SUPERVISOR;
        $leaveRequest->update([
            'store_id' => $employee->store_id,
            'status' => 'pending',
            'workflow_status' => $initialWorkflow,
        ]);
        $this->inAppNotifications->notifyLeaveUpdate($leaveRequest->fresh()->load(['employee.user', 'store']), $user);

        return (new LeaveRequestResource($leaveRequest->fresh()->load(['type', 'employee', 'store'])))
            ->response()
            ->setStatusCode(201);
    }

    public function approve(ApproveLeaveRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorize('approve', $leaveRequest);

        $user = $request->user();
        $comment = $request->validated()['comment'] ?? null;

        if ($this->mobileAccess->isSupervisor($user)) {
            $leaveRequest->update([
                'workflow_status' => LeaveRequest::WORKFLOW_PENDING_MANAGER,
                'supervisor_approved_at' => now(),
                'manager_id' => $user->id,
                'manager_comment' => $comment,
                'status' => 'pending',
            ]);
        } elseif ($this->mobileAccess->isManager($user)) {
            $leaveRequest->update([
                'workflow_status' => LeaveRequest::WORKFLOW_PENDING_HR,
                'manager_approved_at' => now(),
                'manager_id' => $user->id,
                'manager_comment' => $comment,
                'status' => 'pending',
            ]);
        } elseif ($this->mobileAccess->isHr($user)) {
            $leaveRequest->update([
                'workflow_status' => LeaveRequest::WORKFLOW_APPROVED,
                'hr_approved_at' => now(),
                'manager_id' => $user->id,
                'manager_comment' => $comment,
                'status' => 'approved',
            ]);

            if ($leaveRequest->type?->name === 'Annual') {
                $balance = LeaveBalance::query()
                    ->where('employee_id', $leaveRequest->employee_id)
                    ->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->where('year', Carbon::parse($leaveRequest->start_date)->year)
                    ->first();

                if ($balance) {
                    $balance->increment('used_days', $leaveRequest->total_days);
                }
            }
        }

        $this->inAppNotifications->notifyLeaveUpdate($leaveRequest->fresh()->load(['employee.user', 'store']), $user);

        return new LeaveRequestResource($leaveRequest->fresh()->load(['type', 'employee', 'store']));
    }

    public function reject(RejectLeaveRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorize('reject', $leaveRequest);

        $user = $request->user();
        $reason = $request->validated()['reason'];

        $leaveRequest->update([
            'workflow_status' => LeaveRequest::WORKFLOW_REJECTED,
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'manager_id' => $user->id,
            'manager_comment' => $reason,
        ]);
        $this->inAppNotifications->notifyLeaveUpdate($leaveRequest->fresh()->load(['employee.user', 'store']), $user);

        return new LeaveRequestResource($leaveRequest->fresh()->load(['type', 'employee', 'store']));
    }
}
