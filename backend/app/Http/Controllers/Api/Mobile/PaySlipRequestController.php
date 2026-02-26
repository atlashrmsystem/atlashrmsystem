<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ApprovePaySlipRequest;
use App\Http\Requests\Mobile\ListPaySlipRequestsRequest;
use App\Http\Requests\Mobile\RejectPaySlipRequest;
use App\Http\Requests\Mobile\StorePaySlipRequestRequest;
use App\Http\Resources\Mobile\PaySlipRequestResource;
use App\Models\PaySlip;
use App\Models\PaySlipRequest;
use App\Services\InAppNotificationService;
use App\Services\MobileAccessService;
use Illuminate\Http\Request;

class PaySlipRequestController extends Controller
{
    public function __construct(
        private readonly MobileAccessService $mobileAccess,
        private readonly InAppNotificationService $inAppNotifications
    ) {}

    public function index(ListPaySlipRequestsRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        $query = PaySlipRequest::query()->with(['employee', 'store'])->latest();

        if ($this->mobileAccess->isHr($user)) {
            $query->when(
                empty($filters['workflow_status']),
                fn ($q) => $q->where('workflow_status', PaySlipRequest::WORKFLOW_PENDING_HR)
            );
        } elseif ($this->mobileAccess->isManager($user)) {
            $query->whereIn('store_id', $this->mobileAccess->accessibleStoreIds($user));
            $query->when(
                empty($filters['workflow_status']),
                fn ($q) => $q->where('workflow_status', PaySlipRequest::WORKFLOW_PENDING_MANAGER)
            );
        } elseif ($this->mobileAccess->isSupervisor($user)) {
            $query->whereIn('store_id', $this->mobileAccess->accessibleStoreIds($user));
            $query->when(
                empty($filters['workflow_status']),
                fn ($q) => $q->where('workflow_status', PaySlipRequest::WORKFLOW_PENDING_SUPERVISOR)
            );
        } else {
            if (! $user->employee_id) {
                return PaySlipRequestResource::collection(collect());
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
        if (! empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        return PaySlipRequestResource::collection($query->paginate(20));
    }

    public function show(Request $request, PaySlipRequest $paySlipRequest): PaySlipRequestResource
    {
        $this->authorize('view', $paySlipRequest);

        return new PaySlipRequestResource($paySlipRequest->load(['employee', 'store']));
    }

    public function store(StorePaySlipRequestRequest $request)
    {
        $this->authorize('create', PaySlipRequest::class);

        $user = $request->user();
        $employee = $user->employee;
        if (! $employee) {
            abort(404, 'Employee profile not found.');
        }

        $data = $request->validated();
        $month = $data['month'];

        // If payslip already exists, no approval request is needed.
        $alreadyGenerated = PaySlip::query()
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->exists();
        if ($alreadyGenerated) {
            abort(422, 'Payslip for this month already exists.');
        }

        // Avoid duplicate active requests for same month.
        $hasOpenRequest = PaySlipRequest::query()
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->whereIn('workflow_status', [
                PaySlipRequest::WORKFLOW_PENDING_SUPERVISOR,
                PaySlipRequest::WORKFLOW_PENDING_MANAGER,
                PaySlipRequest::WORKFLOW_PENDING_HR,
                PaySlipRequest::WORKFLOW_APPROVED,
            ])
            ->exists();
        if ($hasOpenRequest) {
            abort(422, 'Payslip request for this month already exists.');
        }

        $paySlipRequest = PaySlipRequest::query()->create([
            'employee_id' => $employee->id,
            'store_id' => $employee->store_id,
            'month' => $month,
            'status' => 'pending',
            'workflow_status' => PaySlipRequest::WORKFLOW_PENDING_SUPERVISOR,
            'requested_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);
        $this->inAppNotifications->notifyPaySlipRequestUpdate($paySlipRequest->fresh()->load(['employee.user', 'store']), $user);

        return (new PaySlipRequestResource($paySlipRequest->load(['employee', 'store'])))
            ->response()
            ->setStatusCode(201);
    }

    public function approve(ApprovePaySlipRequest $request, PaySlipRequest $paySlipRequest): PaySlipRequestResource
    {
        $this->authorize('approve', $paySlipRequest);

        $user = $request->user();

        if ($this->mobileAccess->isSupervisor($user)) {
            $paySlipRequest->update([
                'workflow_status' => PaySlipRequest::WORKFLOW_PENDING_MANAGER,
                'supervisor_approved_at' => now(),
                'status' => 'pending',
            ]);
        } elseif ($this->mobileAccess->isManager($user)) {
            $paySlipRequest->update([
                'workflow_status' => PaySlipRequest::WORKFLOW_PENDING_HR,
                'manager_approved_at' => now(),
                'status' => 'pending',
            ]);
        } elseif ($this->mobileAccess->isHr($user)) {
            $paySlipRequest->update([
                'workflow_status' => PaySlipRequest::WORKFLOW_APPROVED,
                'hr_approved_at' => now(),
                'processed_at' => now(),
                'status' => 'approved',
            ]);

            // Create the payslip row so payroll mapping can pick it up.
            PaySlip::firstOrCreate(
                [
                    'employee_id' => $paySlipRequest->employee_id,
                    'month' => $paySlipRequest->month,
                ],
                [
                    'generated_at' => now(),
                ]
            );
        }

        $this->inAppNotifications->notifyPaySlipRequestUpdate($paySlipRequest->fresh()->load(['employee.user', 'store']), $user);

        return new PaySlipRequestResource($paySlipRequest->fresh()->load(['employee', 'store']));
    }

    public function reject(RejectPaySlipRequest $request, PaySlipRequest $paySlipRequest): PaySlipRequestResource
    {
        $this->authorize('reject', $paySlipRequest);

        $paySlipRequest->update([
            'workflow_status' => PaySlipRequest::WORKFLOW_REJECTED,
            'status' => 'rejected',
            'rejected_at' => now(),
            'processed_at' => now(),
            'rejection_reason' => $request->validated()['reason'],
        ]);
        $this->inAppNotifications->notifyPaySlipRequestUpdate($paySlipRequest->fresh()->load(['employee.user', 'store']), $request->user());

        return new PaySlipRequestResource($paySlipRequest->fresh()->load(['employee', 'store']));
    }
}
