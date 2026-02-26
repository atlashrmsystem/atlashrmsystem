<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function todayStatus(Request $request)
    {
        $record = $this->attendanceService->getTodayRecord($request->user()->employee->id);

        return response()->json([
            'status' => 'success',
            'data' => $record,
        ]);
    }

    public function dailySummary(Request $request)
    {
        if (! $request->user()->hasRole(['admin', 'super-admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        $today = now()->toDateString();

        $employeesQuery = Employee::query();
        if (Schema::hasColumn('employees', 'status')) {
            $employeesQuery->where('status', 'active');
        }

        $employees = (clone $employeesQuery)
            ->select('id', 'full_name', 'department', 'job_title')
            ->orderBy('full_name')
            ->get();

        $todayRecords = AttendanceRecord::whereDate('date', $today)
            ->get()
            ->keyBy('employee_id');

        $supposedOnDuty = $employees->count();
        $presentToday = $todayRecords->filter(fn ($record) => ! is_null($record->clock_in_time))->count();
        $currentlyOnDuty = $todayRecords->filter(
            fn ($record) => ! is_null($record->clock_in_time) && is_null($record->clock_out_time)
        )->count();
        $absentToday = max($supposedOnDuty - $presentToday, 0);

        $rows = $employees->map(function ($employee) use ($todayRecords) {
            $record = $todayRecords->get($employee->id);

            return [
                'employee_id' => $employee->id,
                'full_name' => $employee->full_name,
                'department' => $employee->department,
                'job_title' => $employee->job_title,
                'clock_in_time' => $record?->clock_in_time,
                'clock_out_time' => $record?->clock_out_time,
                'status' => $record?->status ?? 'absent',
                'is_on_duty' => (bool) ($record && $record->clock_in_time && ! $record->clock_out_time),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $today,
                'supposed_on_duty' => $supposedOnDuty,
                'present_today' => $presentToday,
                'currently_on_duty' => $currentlyOnDuty,
                'absent_today' => $absentToday,
                'rows' => $rows,
            ],
        ]);
    }

    public function clockIn(Request $request)
    {
        $validated = $request->validate([
            'location' => 'nullable|array',
        ]);

        try {
            $record = $this->attendanceService->clockIn(
                $request->user()->employee->id,
                $validated['location'] ?? []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Clocked in successfully',
                'data' => $record,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function clockOut(Request $request)
    {
        $validated = $request->validate([
            'location' => 'nullable|array',
        ]);

        try {
            $record = $this->attendanceService->clockOut(
                $request->user()->employee->id,
                $validated['location'] ?? []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Clocked out successfully',
                'data' => $record,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function index(Request $request)
    {
        $employeeId = $request->user()->employee->id;

        // If admin/super-admin and employee_id is provided, use that instead
        if ($request->user()->hasRole(['admin', 'super-admin']) && $request->has('employee_id')) {
            $employeeId = $request->employee_id;
        }

        $records = \App\Models\AttendanceRecord::where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }
}
