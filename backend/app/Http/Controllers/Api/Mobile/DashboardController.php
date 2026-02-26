<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\SalesEntry;
use App\Models\Schedule;
use App\Services\MobileAccessService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly MobileAccessService $mobileAccess) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();
        $storeIds = $this->mobileAccess->accessibleStoreIds($user);
        $isSupervisor = $this->mobileAccess->isSupervisor($user);
        $isManager = $this->mobileAccess->isManager($user);

        $pendingLeaveCount = 0;
        if ($this->mobileAccess->isHr($user)) {
            $pendingLeaveCount = LeaveRequest::query()
                ->where('workflow_status', LeaveRequest::WORKFLOW_PENDING_HR)
                ->count();
        } elseif ($isSupervisor && $isManager) {
            $pendingLeaveCount = LeaveRequest::query()
                ->whereIn('store_id', $storeIds)
                ->whereIn('workflow_status', [
                    LeaveRequest::WORKFLOW_PENDING_SUPERVISOR,
                    LeaveRequest::WORKFLOW_PENDING_MANAGER,
                ])
                ->count();
        } elseif ($isManager) {
            $pendingLeaveCount = LeaveRequest::query()
                ->whereIn('store_id', $storeIds)
                ->where('workflow_status', LeaveRequest::WORKFLOW_PENDING_MANAGER)
                ->count();
        } elseif ($isSupervisor) {
            $pendingLeaveCount = LeaveRequest::query()
                ->whereIn('store_id', $storeIds)
                ->where('workflow_status', LeaveRequest::WORKFLOW_PENDING_SUPERVISOR)
                ->count();
        } elseif ($user->employee_id) {
            $pendingLeaveCount = LeaveRequest::query()
                ->where('employee_id', $user->employee_id)
                ->whereIn('workflow_status', [
                    LeaveRequest::WORKFLOW_PENDING_SUPERVISOR,
                    LeaveRequest::WORKFLOW_PENDING_MANAGER,
                    LeaveRequest::WORKFLOW_PENDING_HR,
                ])
                ->count();
        }

        $upcomingSchedules = Schedule::query()
            ->with(['shift', 'store'])
            ->when(
                $user->employee_id && ! $this->mobileAccess->isSupervisor($user) && ! $this->mobileAccess->isManager($user) && ! $this->mobileAccess->isHr($user),
                fn ($q) => $q->where('employee_id', $user->employee_id),
                fn ($q) => $q->whereIn('store_id', $storeIds)
            )
            ->whereDate('date', '>=', $today)
            ->whereDate('date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('date')
            ->limit(10)
            ->get()
            ->map(fn (Schedule $schedule) => [
                'id' => $schedule->id,
                'date' => $schedule->date->toDateString(),
                'is_closing_shift' => $schedule->is_closing_shift,
                'store' => $schedule->store?->name,
                'shift' => $schedule->shift?->name,
                'employee_id' => $schedule->employee_id,
            ])
            ->values();

        $todaySchedulesQuery = Schedule::query()->whereDate('date', $today);
        if (! $this->mobileAccess->isHr($user)) {
            $todaySchedulesQuery->whereIn('store_id', $storeIds);
        }

        $supposedOnDuty = (clone $todaySchedulesQuery)->distinct('employee_id')->count('employee_id');

        $todayAttendanceQuery = AttendanceRecord::query()->whereDate('date', $today);
        if (! $this->mobileAccess->isHr($user)) {
            $todayAttendanceQuery->whereIn('store_id', $storeIds);
        }

        $presentToday = (clone $todayAttendanceQuery)->whereNotNull('clock_in_time')->distinct('employee_id')->count('employee_id');
        $currentlyOnDuty = (clone $todayAttendanceQuery)
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->distinct('employee_id')
            ->count('employee_id');
        $absentToday = max($supposedOnDuty - $presentToday, 0);

        $salesToday = SalesEntry::query()
            ->when(
                ! $this->mobileAccess->isHr($user),
                fn ($q) => $q->whereIn('store_id', $storeIds)
            )
            ->whereDate('date', $today)
            ->sum('amount');

        return response()->json([
            'date' => $today,
            'pending_leave_count' => $pendingLeaveCount,
            'attendance' => [
                'supposed_on_duty' => $supposedOnDuty,
                'present_today' => $presentToday,
                'currently_on_duty' => $currentlyOnDuty,
                'absent_today' => $absentToday,
            ],
            'sales_today' => (float) $salesToday,
            'upcoming_schedules' => $upcomingSchedules,
        ]);
    }
}
