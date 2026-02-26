<?php

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\PaySlipRequest;
use App\Models\SalesEntry;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\AttendanceRecordPolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\PaySlipRequestPolicy;
use App\Policies\SalesEntryPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(PaySlipRequest::class, PaySlipRequestPolicy::class);
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(AttendanceRecord::class, AttendanceRecordPolicy::class);
        Gate::policy(SalesEntry::class, SalesEntryPolicy::class);

        // Super-admin is granted all abilities by default.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
