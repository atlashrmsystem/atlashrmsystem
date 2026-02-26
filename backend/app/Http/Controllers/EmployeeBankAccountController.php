<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeBankAccountController extends Controller
{
    public function show(Employee $employee)
    {
        return response()->json([
            'status' => 'success',
            'data' => $employee->bankAccount,
        ]);
    }

    public function upsert(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'bank_holder_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'iban_number' => 'nullable|string|max:34',
            'account_number' => 'required|string|max:255',
            'account_type' => 'nullable|string|max:100',
        ]);

        $bankAccount = $employee->bankAccount()->updateOrCreate(
            ['employee_id' => $employee->id],
            $validated
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Bank account saved successfully.',
            'data' => $bankAccount->fresh(),
        ]);
    }

    public function destroy(Employee $employee)
    {
        if ($employee->bankAccount) {
            $employee->bankAccount->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Bank account deleted successfully.',
        ]);
    }
}
