<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\LeaveRequest;
use App\Models\Payroll;

class ReportController extends Controller
{
    /**
     * Get a high level summary of the system
     */
    public function summary()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Total Active Headcount
        $headcount = Employee::where('status', 'active')->count();

        // Pending Leave Requests
        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();

        // Open Job Positions
        $openPositions = JobPosting::where('status', 'open')->count();

        // Total Payroll Cost for the current month
        $totalPayroll = Payroll::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'approved')
            ->sum('net_salary');

        // New Hires this month
        $newHires = Employee::whereYear('joining_date', $currentYear)
            ->whereMonth('joining_date', $currentMonth)
            ->count();

        return response()->json([
            'data' => [
                'headcount' => $headcount,
                'pending_leaves' => $pendingLeaves,
                'open_positions' => $openPositions,
                'total_payroll_mtd' => $totalPayroll,
                'new_hires_mtd' => $newHires,
                'period' => now()->format('F Y'),
            ],
        ]);
    }
}
