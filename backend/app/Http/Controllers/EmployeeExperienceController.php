<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeExperienceController extends Controller
{
    public function index(Employee $employee)
    {
        return response()->json([
            'status' => 'success',
            'data' => $employee->experiences()->latest()->get(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'duty_address' => 'nullable|string',
            'working_duration' => 'required|string|max:255',
        ]);

        $experience = $employee->experiences()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Experience added successfully.',
            'data' => $experience,
        ], 201);
    }

    public function update(Request $request, Employee $employee, int $experienceId)
    {
        $experience = $employee->experiences()->findOrFail($experienceId);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'duty_address' => 'nullable|string',
            'working_duration' => 'required|string|max:255',
        ]);

        $experience->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Experience updated successfully.',
            'data' => $experience->fresh(),
        ]);
    }

    public function destroy(Employee $employee, int $experienceId)
    {
        $experience = $employee->experiences()->findOrFail($experienceId);
        $experience->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Experience deleted successfully.',
        ]);
    }
}
