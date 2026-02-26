<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Services\RecruitmentService;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    protected RecruitmentService $recruitmentService;

    public function __construct(RecruitmentService $recruitmentService)
    {
        $this->recruitmentService = $recruitmentService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'interview_date' => 'required|date',
            'interviewer_id' => 'required|exists:users,id',
            'type' => 'required|in:phone,video,in-person',
        ]);

        try {
            $interview = $this->recruitmentService->scheduleInterview($validated);

            return response()->json(['status' => 'success', 'data' => $interview], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function feedback(Request $request, $id)
    {
        $interview = Interview::findOrFail($id);

        $validated = $request->validate([
            'feedback' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $interview->update($validated);

        return response()->json(['status' => 'success', 'data' => $interview]);
    }
}
