<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreSalaryCertificateRequest;
use App\Http\Resources\Mobile\SalaryCertificateRequestResource;
use App\Models\SalaryCertificateRequest;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;

class SalaryCertificateRequestController extends Controller
{
    public function __construct(private readonly InAppNotificationService $inAppNotifications) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = SalaryCertificateRequest::query()->with('employee')->latest();

        if (! $user->hasAnyRole(['admin', 'super-admin'])) {
            if (! $user->employee_id) {
                return SalaryCertificateRequestResource::collection(collect());
            }
            $query->where('employee_id', $user->employee_id);
        } elseif ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return SalaryCertificateRequestResource::collection($query->paginate(20));
    }

    public function store(StoreSalaryCertificateRequest $request)
    {
        $user = $request->user();

        if (! $user->employee_id) {
            abort(404, 'Employee profile not found.');
        }

        if (! $user->can('request salary certificate')) {
            abort(403, 'Forbidden');
        }

        $salaryCertificateRequest = SalaryCertificateRequest::query()->create([
            'employee_id' => $user->employee_id,
            'status' => 'pending',
            'requested_at' => now(),
            'notes' => $request->validated()['notes'] ?? null,
        ]);
        $this->inAppNotifications->notifySalaryCertificateRequestUpdate($salaryCertificateRequest->fresh()->load('employee.user'), $user);

        return (new SalaryCertificateRequestResource($salaryCertificateRequest->load('employee')))
            ->response()
            ->setStatusCode(201);
    }

    public function approve(Request $request, SalaryCertificateRequest $salaryCertificateRequest): SalaryCertificateRequestResource
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Forbidden');
        }

        $salaryCertificateRequest->update([
            'status' => 'approved',
            'processed_at' => now(),
            'notes' => $request->input('notes', $salaryCertificateRequest->notes),
        ]);
        $this->inAppNotifications->notifySalaryCertificateRequestUpdate($salaryCertificateRequest->fresh()->load('employee.user'), $user);

        return new SalaryCertificateRequestResource($salaryCertificateRequest->fresh()->load('employee'));
    }

    public function reject(Request $request, SalaryCertificateRequest $salaryCertificateRequest): SalaryCertificateRequestResource
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Forbidden');
        }

        $salaryCertificateRequest->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'notes' => $request->input('notes', $salaryCertificateRequest->notes),
        ]);
        $this->inAppNotifications->notifySalaryCertificateRequestUpdate($salaryCertificateRequest->fresh()->load('employee.user'), $user);

        return new SalaryCertificateRequestResource($salaryCertificateRequest->fresh()->load('employee'));
    }
}
