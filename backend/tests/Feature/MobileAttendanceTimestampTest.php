<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileAttendanceTimestampTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_attendance_uses_exact_event_time_with_timezone_serialization(): void
    {
        Permission::findOrCreate('clock in/out', 'web');
        $staffRole = Role::findOrCreate('staff', 'web');
        $staffRole->givePermissionTo('clock in/out');

        $employee = Employee::factory()->create();
        $user = User::factory()->create([
            'employee_id' => $employee->id,
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($staffRole);

        $appTimezone = config('app.timezone', 'UTC');
        $clockInMoment = Carbon::create(2026, 2, 26, 7, 22, 0, $appTimezone);
        Carbon::setTestNow($clockInMoment);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = (string) $login->json('access_token');
        $this->assertNotSame('', $token);

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        $clockIn = $this
            ->withHeaders($headers)
            ->postJson('/api/attendance/clock-in');
        $clockIn->assertOk();
        $expectedClockInUtc = $clockInMoment->copy()->utc()->toIso8601String();
        $clockIn->assertJsonPath('data.clock_in', $expectedClockInUtc);
        $clockIn->assertJsonPath('data.clock_in_time', $expectedClockInUtc);
        $this->assertNotNull(
            AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', $clockInMoment->toDateString())
                ->first()
        );

        // Ensure "today" lookup uses the same deterministic date in test runtime.
        Carbon::setTestNow($clockInMoment);

        $today = $this
            ->withHeaders($headers)
            ->getJson('/api/attendance/today');
        $today->assertOk();
        $today->assertJsonPath('data.clock_in', $expectedClockInUtc);
        $today->assertJsonPath('data.clock_in_time', $expectedClockInUtc);

        Carbon::setTestNow();
    }
}
