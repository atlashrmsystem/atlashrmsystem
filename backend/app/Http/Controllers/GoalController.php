<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Services\PerformanceService;
use Exception;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    protected $performanceService;

    public function __construct(PerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    public function index(Request $request)
    {
        $query = Goal::with(['employee', 'performanceCycle']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('performance_cycle_id')) {
            $query->where('performance_cycle_id', $request->performance_cycle_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'numeric|min:0|max:100',
        ]);

        try {
            $goal = $this->performanceService->createGoal($validated);

            return response()->json(['data' => $goal], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, Goal $goal)
    {
        $validated = $request->validate([
            'status' => 'in:pending,in_progress,achieved,missed',
            'progress' => 'integer|min:0|max:100',
        ]);

        $goal->update($validated);

        return response()->json(['data' => $goal]);
    }
}
