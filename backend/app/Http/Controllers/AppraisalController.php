<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Services\PerformanceService;
use Exception;
use Illuminate\Http\Request;

class AppraisalController extends Controller
{
    protected $performanceService;

    public function __construct(PerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    public function index(Request $request)
    {
        $query = Appraisal::with(['employee', 'manager', 'form', 'performanceCycle']);

        // Security check: If not admin/super-admin/manager, restrict to self
        if (! $request->user()->hasRole(['admin', 'super-admin', 'manager'])) {
            $employeeId = $request->user()->employee->id ?? null;
            if (! $employeeId) {
                return response()->json(['data' => []]);
            }
            $query->where('employee_id', $employeeId);
        } else {
            // Admin/Manager can filter
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('manager_id')) {
                $query->where('manager_id', $request->manager_id);
            }
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(Appraisal $appraisal)
    {
        $appraisal->load(['employee', 'manager', 'form', 'performanceCycle', 'responses']);

        return response()->json(['data' => $appraisal]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'form_id' => 'required|exists:appraisal_forms,id',
        ]);

        try {
            $appraisal = $this->performanceService->createAppraisal($validated);

            return response()->json(['data' => $appraisal], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function submit(Request $request, int $id)
    {
        $validated = $request->validate([
            'responses' => 'required|array',
            'responses.*.question_id' => 'required|string',
            'responses.*.response_text' => 'nullable|string',
            'responses.*.score' => 'nullable|integer|min:1|max:5',
        ]);

        try {
            $appraisal = $this->performanceService->submitAppraisal($id, $validated['responses']);

            return response()->json(['data' => $appraisal]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function review(Request $request, int $id)
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:employees,id',
            'final_score' => 'required|numeric|min:0|max:5',
            'comments' => 'nullable|string',
        ]);

        try {
            $appraisal = $this->performanceService->reviewAppraisal($id, $validated['manager_id'], $validated);

            return response()->json(['data' => $appraisal]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
