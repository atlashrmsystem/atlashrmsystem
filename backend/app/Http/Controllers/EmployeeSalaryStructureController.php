<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeSalaryStructureController extends Controller
{
    public function show(Employee $employee)
    {
        return response()->json([
            'status' => 'success',
            'data' => $employee->salaryStructure,
        ]);
    }

    public function upsert(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'salary_type' => 'required|string|max:50',
            'total_salary' => 'nullable|numeric|min:0',
            'basic' => 'required|numeric|min:0',
            'house_rent' => 'nullable|numeric|min:0',
            'medical' => 'nullable|numeric|min:0',
            'conveyance' => 'nullable|numeric|min:0',
            'deduction_penalty' => 'nullable|numeric|min:0',
            'deduction_others' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'effective_from' => 'nullable|date',
        ]);

        $grossFromParts = (float) ($validated['basic'] ?? 0)
            + (float) ($validated['house_rent'] ?? 0)
            + (float) ($validated['medical'] ?? 0)
            + (float) ($validated['conveyance'] ?? 0);

        if (! array_key_exists('total_salary', $validated) || is_null($validated['total_salary'])) {
            $validated['total_salary'] = $grossFromParts;
        }

        $structure = $employee->salaryStructure()->updateOrCreate(
            ['employee_id' => $employee->id],
            $validated
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Salary structure saved successfully.',
            'data' => $structure->fresh(),
        ]);
    }

    public function destroy(Employee $employee)
    {
        if ($employee->salaryStructure) {
            $employee->salaryStructure->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Salary structure deleted successfully.',
        ]);
    }
}
