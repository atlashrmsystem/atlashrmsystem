<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOnboarding;
use App\Models\OnboardingChecklist;
use App\Services\RecruitmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OnboardingController extends Controller
{
    protected RecruitmentService $recruitmentService;

    public function __construct(RecruitmentService $recruitmentService)
    {
        $this->recruitmentService = $recruitmentService;
    }

    public function checklists()
    {
        $checklists = OnboardingChecklist::orderBy('order')->get();

        return response()->json(['status' => 'success', 'data' => $checklists]);
    }

    public function assign(Request $request, $employeeId)
    {
        try {
            $this->recruitmentService->assignOnboardingChecklists($employeeId);

            return response()->json([
                'status' => 'success',
                'message' => 'Mandatory onboarding tasks assigned to employee.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function complete(Request $request, $id)
    {
        $task = EmployeeOnboarding::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $task->update([
            'completed_at' => Carbon::now(),
            'notes' => $validated['notes'] ?? $task->notes,
        ]);

        return response()->json(['status' => 'success', 'data' => $task]);
    }
}
