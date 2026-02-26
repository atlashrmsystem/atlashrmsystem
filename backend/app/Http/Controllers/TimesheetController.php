<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Services\TimesheetService;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    protected TimesheetService $timesheetService;

    public function __construct(TimesheetService $timesheetService)
    {
        $this->timesheetService = $timesheetService;
    }

    /**
     * Get timesheets for the authenticated employee.
     */
    public function index(Request $request)
    {
        $query = Timesheet::query()->with('employee');

        // If not admin/super-admin, restrict to their own timesheets
        if (! $request->user()->hasRole(['admin', 'super-admin'])) {
            $employeeId = $request->user()->employee->id ?? null;
            if (! $employeeId) {
                return response()->json(['status' => 'success', 'data' => []]);
            }
            $query->where('employee_id', $employeeId);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        $timesheets = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get(); // Using get() instead of paginate for frontend table parsing

        return response()->json([
            'status' => 'success',
            'data' => $timesheets,
        ]);
    }

    /**
     * Generate timesheet for a specific month and year.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $user = $request->user();

            if ($user->hasRole(['admin', 'super-admin'])) {
                $timesheets = $this->timesheetService->generateTimesheetsForAllEmployees(
                    $validated['month'],
                    $validated['year'],
                    'approved'
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Timesheets generated successfully for all employees',
                    'generated_count' => $timesheets->count(),
                    'data' => $timesheets->values(),
                ]);
            }

            $employeeId = $user->employee->id ?? null;
            if (! $employeeId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No employee profile linked to this account.',
                ], 422);
            }

            $timesheet = $this->timesheetService->generateTimesheet(
                $employeeId,
                $validated['month'],
                $validated['year'],
                'draft'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Timesheet generated successfully',
                'data' => $timesheet,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
