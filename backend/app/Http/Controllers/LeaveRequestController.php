<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    protected LeaveService $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Display a listing of leave requests for the logged in user's employee profile
     * Note: In a real app we would map auth()->user() to an Employee profile.
     * Here we accept employee_id for demonstration purposes.
     */
    public function index(Request $request)
    {
        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|string',
        ]);

        $isPrivileged = $request->user()->hasRole(['admin', 'super-admin', 'manager', 'supervisor']);
        $employeeId = $request->user()->employee->id ?? null;

        $query = LeaveRequest::with(['type', 'employee'])->latest();

        if (! $isPrivileged) {
            if (! $employeeId) {
                return response()->json($query->whereRaw('1 = 0')->paginate(15));
            }
            $query->where('employee_id', $employeeId);
        } elseif ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Submit a new leave request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'attachment_path' => 'nullable|string',
        ]);

        $isPrivileged = $request->user()->hasRole(['admin', 'super-admin', 'manager', 'supervisor']);
        if (! $isPrivileged) {
            $selfEmployeeId = $request->user()->employee->id ?? null;
            if (! $selfEmployeeId) {
                return response()->json(['error' => 'Employee profile not found.'], 404);
            }
            $validated['employee_id'] = $selfEmployeeId;
        } elseif (empty($validated['employee_id'])) {
            return response()->json(['error' => 'employee_id is required for admin requests.'], 422);
        }

        $employee = Employee::findOrFail($validated['employee_id']);

        try {
            $leaveRequest = $this->leaveService->requestLeave($employee, $validated);

            return response()->json($leaveRequest, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get Leave Balances for an employee
     */
    public function balances(Request $request)
    {
        $request->validate(['employee_id' => 'nullable|numeric']);

        $isPrivileged = $request->user()->hasRole(['admin', 'super-admin', 'manager', 'supervisor']);
        $employeeId = null;

        if (! $isPrivileged) {
            $employeeId = $request->user()->employee->id ?? null;
            if (! $employeeId) {
                return response()->json([]);
            }
        } else {
            $employeeId = $request->input('employee_id');
            if (empty($employeeId)) {
                return response()->json(['error' => 'employee_id is required for admin requests.'], 422);
            }
        }

        $currentYear = Carbon::now()->year;
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->get();

        return response()->json($balances);
    }

    /**
     * Manager Approval actions
     */
    public function approve(Request $request, int $id)
    {
        if (! $request->user()->hasRole(['admin', 'super-admin', 'manager', 'supervisor'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Auth user would be the manager
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['error' => 'Request is already processed.'], 422);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'manager_id' => $request->user()->id,
            'manager_comment' => $request->comment,
        ]);

        // If Annual leave, deduct from balance
        if ($leaveRequest->type->name === 'Annual') {
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', Carbon::parse($leaveRequest->start_date)->year)
                ->first();

            if ($balance) {
                $balance->increment('used_days', $leaveRequest->total_days);
            }
        }

        return response()->json($leaveRequest);
    }

    public function reject(Request $request, int $id)
    {
        if (! $request->user()->hasRole(['admin', 'super-admin', 'manager', 'supervisor'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['error' => 'Request is already processed.'], 422);
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'manager_id' => $request->user()->id,
            'manager_comment' => $request->comment,
        ]);

        return response()->json($leaveRequest);
    }
}
