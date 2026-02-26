<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Timesheet;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollService
{
    private function toMoney($value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    /**
     * Generate payroll for all active employees for a given month/year
     */
    public function generateMonthlyPayroll(int $year, int $month): array
    {
        $employeesQuery = Employee::query()->with('salaryStructure');

        // Keep compatibility with schemas where employee status may not exist.
        if (Schema::hasColumn('employees', 'status')) {
            $employeesQuery->where('status', 'active');
        }

        $employees = $employeesQuery->get();
        $generatedCount = 0;
        $errors = [];

        foreach ($employees as $employee) {
            try {
                DB::transaction(function () use ($employee, $year, $month) {
                    $this->calculateEmployeePayroll($employee, $year, $month);
                });
                $generatedCount++;
            } catch (Exception $e) {
                $errors[] = "Employee ID {$employee->id}: ".$e->getMessage();
            }
        }

        return [
            'success' => true,
            'message' => "Successfully generated $generatedCount payroll records.",
            'errors' => $errors,
        ];
    }

    /**
     * Calculate and save payroll for a single employee
     */
    private function calculateEmployeePayroll(Employee $employee, int $year, int $month): Payroll
    {
        // Check if payroll already exists
        $existing = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing && in_array($existing->status, ['approved', 'paid'])) {
            throw new Exception('Payroll already generated and approved/paid.');
        }

        // Fetch timesheet to integrate overtime & lateness
        $timesheet = Timesheet::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'approved') // Only use approved timesheets to calculate pay
            ->first();

        $salaryStructure = $employee->relationLoaded('salaryStructure')
            ? $employee->salaryStructure
            : $employee->salaryStructure()->first();

        $basicSalary = $this->toMoney($salaryStructure?->basic ?? $employee->basic_salary);
        $hourlyRate = $basicSalary / 176;
        $overtimeRate = $hourlyRate * 1.25; // standard 1.25x overtime multiplier
        $latenessRate = $hourlyRate / 60;   // per minute penalty

        $overtimePay = 0;
        $latenessDeduction = 0;
        $allowances = [
            'house_rent' => $this->toMoney($salaryStructure?->house_rent),
            'medical' => $this->toMoney($salaryStructure?->medical),
            'conveyance' => $this->toMoney($salaryStructure?->conveyance),
        ];

        if (! $salaryStructure && is_array($employee->allowances)) {
            $allowances['house_rent'] = $this->toMoney($employee->allowances['housing'] ?? $allowances['house_rent']);
            $allowances['conveyance'] = $this->toMoney($employee->allowances['transport'] ?? $allowances['conveyance']);
            $allowances['medical'] = $this->toMoney($employee->allowances['medical'] ?? $allowances['medical']);
        }

        $totalAllowances = array_sum($allowances);
        $fixedDeductions = [
            'penalty' => $this->toMoney($salaryStructure?->deduction_penalty),
            'others' => $this->toMoney($salaryStructure?->deduction_others),
            'advance_payment' => $this->toMoney($salaryStructure?->advance_payment),
        ];

        if ($timesheet) {
            $overtimeHours = ($timesheet->total_overtime_minutes ?? 0) / 60;
            $latenessMinutes = (int) ($timesheet->total_late_minutes ?? 0);

            $overtimePay = $overtimeHours * $overtimeRate;
            $latenessDeduction = $latenessMinutes * $latenessRate;
        }

        $deductions = $fixedDeductions;
        $deductions['lateness'] = round($latenessDeduction, 2);
        $totalDeductions = array_sum($deductions);

        $netSalary = $basicSalary + $totalAllowances + $overtimePay - $totalDeductions;

        $payrollData = [
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year,
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'overtime_pay' => $overtimePay,
            'net_salary' => $netSalary,
            'status' => 'draft',
        ];

        if ($existing) {
            $existing->update($payrollData);

            return $existing;
        }

        return Payroll::create($payrollData);
    }

    /**
     * Generate WPS Export structure for a given month/year
     * Maps to standard SIF (Salary Information File) layout.
     */
    public function generateWpsSif(int $year, int $month): array
    {
        $payrolls = Payroll::with('employee.bankAccount')
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'approved') // Usually only export approved payrolls
            ->get();

        $wpsRows = [];

        // Expected Row Format roughly maps to:
        // 14 byte routing code, Employee ID, Agent ID, Net Salary, Salary Period, ...
        // We'll generate a generalized array useful for a CSV
        foreach ($payrolls as $payroll) {
            $emp = $payroll->employee;
            $bankAccount = $emp?->bankAccount;
            $allowances = (array) $payroll->allowances;
            $housing = (float) ($allowances['house_rent'] ?? $allowances['housing'] ?? 0);
            $medical = (float) ($allowances['medical'] ?? 0);
            $conveyance = (float) ($allowances['conveyance'] ?? $allowances['transport'] ?? 0);

            // In a real scenario, these IDs map to actual visa or ministry IDs.
            // basic_salary should represent standard pay
            // housing, transport mapped to 'Fixed Income'
            // deductions mapped to 'Variable Deductions'

            $wpsRows[] = [
                'Employee ID' => $emp->id,
                'Employee Name' => $emp->full_name,
                'Bank Routing Code' => $bankAccount?->branch_name ?? '',
                'Bank Account Number' => $bankAccount?->iban_number ?? $bankAccount?->account_number ?? '',
                'Basic Salary' => number_format($payroll->basic_salary, 2, '.', ''),
                'Housing' => number_format($housing, 2, '.', ''),
                'Other Incomes' => number_format($medical + $conveyance + (float) $payroll->overtime_pay, 2, '.', ''),
                'Deductions' => number_format(array_sum((array) $payroll->deductions), 2, '.', ''),
                'Net Salary' => number_format($payroll->net_salary, 2, '.', ''),
                'Salary Month/Year' => sprintf('%02d/%04d', $month, $year),
            ];
        }

        return $wpsRows;
    }
}
