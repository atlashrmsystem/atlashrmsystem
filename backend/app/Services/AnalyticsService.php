<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * Get overall HR dashboard analytics (cached for 1 hour)
     */
    public function getDashboardAnalytics(): array
    {
        return Cache::remember('hr_analytics_dashboard', 3600, function () {
            return [
                'headcount' => $this->getHeadcountOverview(),
                'leaves' => $this->getLeaveAnalytics(),
                'performance' => $this->getPerformanceOverview(),
            ];
        });
    }

    /**
     * Get a list of employees sorted by attrition risk
     */
    public function getAttritionRiskReport(): array
    {
        // Don't cache this heavily as risk changes, maybe 15 minutes or just calculate on the fly for specific HR users
        $employees = Employee::with(['leaveRequests' => function ($query) {
            $query->where('status', 'approved')->where('created_at', '>=', now()->subMonths(3));
        }, 'appraisals'])->get();

        $risks = [];

        foreach ($employees as $employee) {
            $score = 0;
            $reasons = [];

            // 1. Frequent Sick Leave (e.g. > 5 days in last 3 months)
            $recentSickDays = $employee->leaveRequests
                ->where('type', 'sick')
                ->sum('days');

            if ($recentSickDays > 5) {
                $score++;
                $reasons[] = "High recent sick leave ({$recentSickDays} days)";
            }

            // 2. Low Performance (latest appraisal score is lowest possible, assuming 1-5 scale)
            $latestAppraisal = $employee->appraisals->sortByDesc('created_at')->first();
            if ($latestAppraisal && $latestAppraisal->final_score !== null && $latestAppraisal->final_score <= 2) {
                $score++;
                $reasons[] = "Recent low performance score ({$latestAppraisal->final_score})";
            }

            // 3. Visa expiring within 30 days
            if ($employee->visa_expiry && Carbon::parse($employee->visa_expiry)->isPast() == false && Carbon::parse($employee->visa_expiry)->diffInDays(now()) <= 30) {
                $score++;
                $reasons[] = 'Visa expires within 30 days';
            }

            $riskLevel = 'Low';
            if ($score >= 2) {
                $riskLevel = 'High';
            } elseif ($score == 1) {
                $riskLevel = 'Medium';
            }

            $risks[] = [
                'employee_id' => $employee->id,
                'name' => $employee->full_name,
                'department' => $employee->department,
                'risk_score' => $score,
                'risk_level' => $riskLevel,
                'reasons' => $reasons,
            ];
        }

        // Sort by highest risk first
        usort($risks, function ($a, $b) {
            return $b['risk_score'] <=> $a['risk_score'];
        });

        return $risks;
    }

    private function getHeadcountOverview(): array
    {
        $total = Employee::count();
        $byDepartment = Employee::query()
            ->selectRaw('department, count(*) as count')
            ->groupBy('department')
            ->pluck('count', 'department')
            ->toArray();

        return [
            'total' => $total,
            'by_department' => $byDepartment,
        ];
    }

    private function getLeaveAnalytics(): array
    {
        return [
            'pending_requests' => LeaveRequest::where('status', 'pending')->count(),
            'employees_on_leave_today' => LeaveRequest::where('status', 'approved')
                ->where('start_date', '<=', today())
                ->where('end_date', '>=', today())
                ->count(),
        ];
    }

    private function getPerformanceOverview(): array
    {
        $avgScore = Appraisal::where('status', 'completed')
            ->whereNotNull('final_score')
            ->avg('final_score');

        return [
            'average_company_score' => round($avgScore, 2) ?? 0,
        ];
    }
}
