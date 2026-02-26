<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeRepository
{
    /**
     * Get paginated list of employees with relations
     */
    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Employee::query()->with([
            'manager',
            'store.brand:id,name',
            'contracts' => function ($contractQuery) {
                $contractQuery->latest('end_date');
            },
        ]);

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('full_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('store.brand', function ($brandQuery) use ($search): void {
                        $brandQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Count employees that currently have at least one active contract.
     */
    public function countEmployeesWithActiveContract(): int
    {
        $today = now()->toDateString();

        return Employee::whereHas('contracts', function ($query) use ($today) {
            $query->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today);
        })->count();
    }

    /**
     * Count employees with contracts ending soon (default: next 30 days).
     */
    public function countEmployeesWithContractsExpiringSoon(int $days = 30): int
    {
        $today = now()->startOfDay()->toDateString();
        $targetDate = now()->addDays($days)->endOfDay()->toDateString();

        return Employee::whereHas('contracts', function ($query) use ($today, $targetDate) {
            $query->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $targetDate);
        })->count();
    }

    /**
     * Create a new employee
     */
    public function create(array $data): Employee
    {
        if (isset($data['photo'])) {
            $data['photo_path'] = $data['photo']->store('employees/photos', 'public');
            unset($data['photo']);
        }

        return Employee::create($data);
    }

    /**
     * Find an employee by ID
     */
    public function findById(int $id): ?Employee
    {
        return Employee::with([
            'manager',
            'contracts',
            'store',
            'user',
            'attendanceRecords' => function ($query) {
                $query->latest('date')->take(30); // Last 30 days
            },
            'educations' => function ($query) {
                $query->orderByDesc('passing_year')->orderByDesc('id');
            },
            'experiences' => function ($query) {
                $query->latest();
            },
            'relatives' => function ($query) {
                $query->latest();
            },
            'bankAccount',
            'documents' => function ($query) {
                $query->latest();
            },
            'salaryStructure',
        ])->findOrFail($id);
    }

    /**
     * Update an existing employee
     */
    public function update(Employee $employee, array $data): Employee
    {
        if (isset($data['photo'])) {
            // Delete old photo if exists
            if ($employee->photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo_path);
            }
            $data['photo_path'] = $data['photo']->store('employees/photos', 'public');
            unset($data['photo']);
        }
        $employee->update($data);

        return $employee;
    }

    /**
     * Delete an employee
     */
    public function delete(Employee $employee): bool
    {
        return $employee->delete();
    }
}
