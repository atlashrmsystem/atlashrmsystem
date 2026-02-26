<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sick_leave_brackets_calculation()
    {
        // 1. Setup Data
        $leaveType = LeaveType::create([
            'name' => 'Sick',
            'is_paid' => true,
        ]);

        $employee = Employee::factory()->create();

        $service = new LeaveService;

        // 2. Mock a new 90 day request directly
        $request = new LeaveRequest([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-30',
            'total_days' => 90,
        ]);

        // 3. Test calculation (assuming 0 taken days prior in DB)
        $brackets = $service->calculateSickLeavePayBracket($employee, $request);

        // UAE Law: First 15 full, Next 30 (16-45) half, Next 45 (46-90) unpaid
        $this->assertEquals(15, $brackets['full_pay_days']);
        $this->assertEquals(30, $brackets['half_pay_days']);
        $this->assertEquals(45, $brackets['unpaid_days']);
    }

    public function test_sick_leave_brackets_throws_if_exceeding_90_days()
    {
        $leaveType = LeaveType::create(['name' => 'Sick']);
        $employee = Employee::factory()->create();
        $service = new LeaveService;

        $request = new LeaveRequest([
            'employee_id' => $employee->id,
            'start_date' => '2024-01-01',
            'total_days' => 91,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Exceeded 90 days maximum');

        $service->calculateSickLeavePayBracket($employee, $request);
    }
}
