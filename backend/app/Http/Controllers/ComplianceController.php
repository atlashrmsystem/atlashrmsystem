<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ComplianceController extends Controller
{
    /**
     * Get Emiratisation statistics grouped by department
     */
    public function emiratisationStats()
    {
        $employeesQuery = Employee::query();
        if (Schema::hasColumn('employees', 'status')) {
            $employeesQuery->where('status', 'active');
        }

        $employees = $employeesQuery->get();

        $totalHeadcount = $employees->count();
        $totalNationals = $employees->where('nationality', 'United Arab Emirates')->count();

        $overallPercentage = $totalHeadcount > 0 ? round(($totalNationals / $totalHeadcount) * 100, 2) : 0;

        $departments = $employees->groupBy('department')->map(function ($group) {
            $count = $group->count();
            $nationals = $group->where('nationality', 'United Arab Emirates')->count();

            return [
                'total' => $count,
                'nationals' => $nationals,
                'percentage' => $count > 0 ? round(($nationals / $count) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'data' => [
                'overall_target' => 2.0, // Standard 2% target (configurable later)
                'overall_percentage' => $overallPercentage,
                'total_headcount' => $totalHeadcount,
                'total_nationals' => $totalNationals,
                'departments' => $departments,
            ],
        ]);
    }

    /**
     * Get employees with approaching document expirations
     */
    public function expiringDocuments()
    {
        $now = now();
        $thirtyDays = now()->addDays(30);
        $ninetyDays = now()->addDays(90);

        // Fetch employees with active status (if available) and valid expirations
        $employeesQuery = Employee::query();
        if (Schema::hasColumn('employees', 'status')) {
            $employeesQuery->where('status', 'active');
        }

        $employees = $employeesQuery
            ->where(function ($q) {
                $q->whereNotNull('passport_expiry')
                    ->orWhereNotNull('visa_expiry');
            })
            ->get();

        $expiring = [];

        foreach ($employees as $employee) {
            $passportDate = $employee->passport_expiry ? Carbon::parse($employee->passport_expiry) : null;
            $visaDate = $employee->visa_expiry ? Carbon::parse($employee->visa_expiry) : null;

            if ($passportDate && $passportDate->between($now, $ninetyDays)) {
                $days = $now->diffInDays($passportDate, false);
                $urgency = $days <= 30 ? 'critical' : 'warning';

                $expiring[] = [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'document' => 'Passport',
                    'expiry_date' => $passportDate->toDateString(),
                    'days_remaining' => $days,
                    'urgency' => $urgency,
                ];
            }

            if ($visaDate && $visaDate->between($now, $ninetyDays)) {
                $days = $now->diffInDays($visaDate, false);
                $urgency = $days <= 30 ? 'critical' : 'warning';

                $expiring[] = [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'document' => 'Visa',
                    'expiry_date' => $visaDate->toDateString(),
                    'days_remaining' => $days,
                    'urgency' => $urgency,
                ];
            }

            // Handle already expired documents
            if ($passportDate && $passportDate->isPast()) {
                $expiring[] = [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'document' => 'Passport',
                    'expiry_date' => $passportDate->toDateString(),
                    'days_remaining' => 0,
                    'urgency' => 'expired',
                ];
            }

            if ($visaDate && $visaDate->isPast()) {
                $expiring[] = [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'document' => 'Visa',
                    'expiry_date' => $visaDate->toDateString(),
                    'days_remaining' => 0,
                    'urgency' => 'expired',
                ];
            }
        }

        // Sort by most urgent first
        usort($expiring, function ($a, $b) {
            return $a['days_remaining'] <=> $b['days_remaining'];
        });

        return response()->json(['data' => $expiring]);
    }
}
