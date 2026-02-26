<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeEducationController extends Controller
{
    public function index(Employee $employee)
    {
        return response()->json([
            'status' => 'success',
            'data' => $employee->educations()->orderByDesc('passing_year')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'degree_name' => 'required|string|max:255',
            'institute_name' => 'required|string|max:255',
            'result' => 'nullable|string|max:255',
            'passing_year' => 'required|integer|min:1950|max:2100',
        ]);

        $education = $employee->educations()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Education added successfully.',
            'data' => $education,
        ], 201);
    }

    public function update(Request $request, Employee $employee, int $educationId)
    {
        $education = $employee->educations()->findOrFail($educationId);

        $validated = $request->validate([
            'degree_name' => 'required|string|max:255',
            'institute_name' => 'required|string|max:255',
            'result' => 'nullable|string|max:255',
            'passing_year' => 'required|integer|min:1950|max:2100',
        ]);

        $education->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Education updated successfully.',
            'data' => $education->fresh(),
        ]);
    }

    public function destroy(Employee $employee, int $educationId)
    {
        $education = $employee->educations()->findOrFail($educationId);
        $education->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Education deleted successfully.',
        ]);
    }
}
