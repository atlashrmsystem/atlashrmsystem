<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        $query = Payroll::with('employee');
        $isPrivileged = $request->user()->hasRole(['admin', 'super-admin']);

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if (! $isPrivileged) {
            $employeeId = $request->user()->employee->id ?? null;
            if (! $employeeId) {
                return response()->json(['data' => []]);
            }
            $query->where('employee_id', $employeeId);
        } elseif ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function generate(Request $request)
    {
        if (! $request->user()->hasRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
        ]);

        $result = $this->payrollService->generateMonthlyPayroll($request->year, $request->month);

        return response()->json($result);
    }

    public function exportWps(Request $request)
    {
        if (! $request->user()->hasRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
        ]);

        $rows = $this->payrollService->generateWpsSif($request->year, $request->month);

        if (empty($rows)) {
            return response()->json(['message' => 'No approved payrolls found for this period to export.'], 404);
        }

        // Generate CSV content
        $headers = array_keys($rows[0]);
        $csvContent = implode(',', $headers)."\n";

        foreach ($rows as $row) {
            $csvContent .= implode(',', array_values($row))."\n";
        }

        $filename = "WPS_Export_{$request->year}_{$request->month}.csv";

        return Response::make($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function update(Request $request, Payroll $payroll)
    {
        if (! $request->user()->hasRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,approved,paid',
        ]);

        if ($validated['status'] == 'paid' && $payroll->status != 'paid') {
            $validated['paid_at'] = now();
        }

        $payroll->update($validated);

        return response()->json(['data' => $payroll->load('employee')]);
    }
}
