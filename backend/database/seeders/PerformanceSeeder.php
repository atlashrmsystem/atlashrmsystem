<?php

namespace Database\Seeders;

use App\Models\Appraisal;
use App\Models\AppraisalForm;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\PerformanceCycle;
use Illuminate\Database\Seeder;

class PerformanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create an active performance cycle
        $cycle = PerformanceCycle::create([
            'name' => 'Q1 2026 Review',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        // 2. Create an Appraisal Form
        $form = AppraisalForm::create([
            'name' => 'Standard Quarterly Appraisal',
            'structure' => [
                ['id' => 'q1', 'question' => 'How well did the employee meet their core objectives?', 'type' => 'rating'],
                ['id' => 'q2', 'question' => 'What are the key areas for improvement?', 'type' => 'text'],
            ],
        ]);

        // 3. Ensure we have employees with managers for testing
        // Creating a manager
        $manager = Employee::firstOrCreate(
            ['email' => 'manager@atlas.com'],
            [
                'full_name' => 'Sarah Manager',
                'phone' => '1234567890',
                'passport_number' => 'A12345678',
                'job_title' => 'Engineering Manager',
                'department' => 'Engineering',
                'joining_date' => '2024-01-01',
                'basic_salary' => 20000,
            ]
        );

        // Creating a direct report
        $employee = Employee::firstOrCreate(
            ['email' => 'employee@atlas.com'],
            [
                'full_name' => 'John Doe',
                'phone' => '0987654321',
                'passport_number' => 'B98765432',
                'job_title' => 'Software Engineer',
                'department' => 'Engineering',
                'joining_date' => '2025-01-01',
                'basic_salary' => 10000,
                'manager_id' => $manager->id,
            ]
        );

        // 4. Create Goals
        Goal::create([
            'employee_id' => $employee->id,
            'performance_cycle_id' => $cycle->id,
            'title' => 'Ship Module 2',
            'description' => 'Complete the Performance Management module within deadline.',
            'status' => 'in_progress',
            'weight' => 50.00,
        ]);

        Goal::create([
            'employee_id' => $employee->id,
            'performance_cycle_id' => $cycle->id,
            'title' => 'Improve Code Coverage',
            'description' => 'Write feature tests to reach 80% coverage.',
            'status' => 'pending',
            'weight' => 50.00,
        ]);

        // 5. Create a Pending Appraisal
        Appraisal::create([
            'employee_id' => $employee->id,
            'performance_cycle_id' => $cycle->id,
            'form_id' => $form->id,
            'manager_id' => $manager->id,
            'status' => 'draft',
        ]);

        // Also attach a goal to the first user we created earlier so the default view is populated
        if ($firstEmployee = Employee::first()) {
            Goal::create([
                'employee_id' => $firstEmployee->id,
                'performance_cycle_id' => $cycle->id,
                'title' => 'General Onboarding',
                'description' => 'Complete all mandatory training sessions.',
                'status' => 'in_progress',
                'weight' => 100.00,
            ]);

            Appraisal::create([
                'employee_id' => $firstEmployee->id,
                'performance_cycle_id' => $cycle->id,
                'form_id' => $form->id,
                'manager_id' => $manager->id, // Default manager
                'status' => 'draft',
            ]);
        }
    }
}
