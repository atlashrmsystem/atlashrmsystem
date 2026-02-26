<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $annual = \App\Models\LeaveType::firstOrCreate(['name' => 'Annual'], ['default_days' => 30, 'is_paid' => true]);
        $sick = \App\Models\LeaveType::firstOrCreate(['name' => 'Sick'], ['default_days' => 15, 'requires_attachment' => true]);
        $maternity = \App\Models\LeaveType::firstOrCreate(['name' => 'Maternity'], ['default_days' => 45, 'is_paid' => true, 'requires_attachment' => true]);

        $employee = \App\Models\Employee::first();
        if ($employee) {
            $year = now()->year;
            \App\Models\LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $annual->id, 'year' => $year, 'balance_days' => 30, 'used_days' => 5]);
            \App\Models\LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $sick->id, 'year' => $year, 'balance_days' => 15, 'used_days' => 0]);

            \App\Models\LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $annual->id,
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(10)->format('Y-m-d'),
                'total_days' => 5,
                'reason' => 'Family vacation',
                'status' => 'pending',
            ]);

            \App\Models\LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $sick->id,
                'start_date' => now()->subDays(10)->format('Y-m-d'),
                'end_date' => now()->subDays(8)->format('Y-m-d'),
                'total_days' => 2,
                'reason' => 'Flu',
                'status' => 'approved',
            ]);
        }
    }
}
