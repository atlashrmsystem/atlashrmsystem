<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TimesheetService
{
    /**
     * Generate or update a timesheet for a specific month and year.
     */
    public function generateTimesheet(int $employeeId, int $month, int $year, string $status = 'draft'): Timesheet
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all attendance records for the month
        $records = AttendanceRecord::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $totalPresentDays = 0;
        $totalAbsentDays = 0; // Can be enhanced by comparing with expected working days
        $totalLateMinutes = 0;
        $totalOvertimeMinutes = 0;

        foreach ($records as $record) {
            $recordDate = Carbon::parse($record->date)->toDateString();

            if ($record->status === 'present' || $record->status === 'late') {
                $totalPresentDays++;
            }

            // Calculate late minutes
            if ($record->status === 'late' && $record->clock_in_time) {
                // Expected start time (e.g., 09:00 AM)
                $expectedStart = Carbon::parse($recordDate.' 09:00:00');
                if ($record->clock_in_time->greaterThan($expectedStart)) {
                    $totalLateMinutes += $record->clock_in_time->diffInMinutes($expectedStart);
                }
            }

            // Calculate overtime minutes (if clocked out after expected end time, e.g., 06:00 PM)
            if ($record->clock_out_time) {
                $expectedEnd = Carbon::parse($recordDate.' 18:00:00');
                if ($record->clock_out_time->greaterThan($expectedEnd)) {
                    $totalOvertimeMinutes += $record->clock_out_time->diffInMinutes($expectedEnd);
                }
            }
        }

        return Timesheet::updateOrCreate(
            ['employee_id' => $employeeId, 'month' => $month, 'year' => $year],
            [
                'total_present_days' => $totalPresentDays,
                'total_absent_days' => $totalAbsentDays,
                'total_late_minutes' => $totalLateMinutes,
                'total_overtime_minutes' => $totalOvertimeMinutes,
                'status' => $status,
            ]
        );
    }

    /**
     * Generate timesheets for all employees for a given month/year.
     */
    public function generateTimesheetsForAllEmployees(int $month, int $year, string $status = 'approved'): Collection
    {
        $employees = Employee::query();

        // Only filter by status if this legacy column exists.
        if (Schema::hasColumn('employees', 'status')) {
            $employees->where('status', 'active');
        }

        return $employees->pluck('id')->map(function (int $employeeId) use ($month, $year, $status) {
            return $this->generateTimesheet($employeeId, $month, $year, $status);
        });
    }
}
