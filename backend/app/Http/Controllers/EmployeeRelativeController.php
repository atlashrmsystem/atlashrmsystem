<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeRelativeController extends Controller
{
    public function index(Employee $employee)
    {
        return response()->json([
            'status' => 'success',
            'data' => $employee->relatives()->latest()->get(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $relative = $employee->relatives()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Relative added successfully.',
            'data' => $relative,
        ], 201);
    }

    public function update(Request $request, Employee $employee, int $relativeId)
    {
        $relative = $employee->relatives()->findOrFail($relativeId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $relative->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Relative updated successfully.',
            'data' => $relative->fresh(),
        ]);
    }

    public function destroy(Employee $employee, int $relativeId)
    {
        $relative = $employee->relatives()->findOrFail($relativeId);
        $relative->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Relative deleted successfully.',
        ]);
    }
}
