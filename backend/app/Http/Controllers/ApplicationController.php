<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\RecruitmentService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    protected RecruitmentService $recruitmentService;

    public function __construct(RecruitmentService $recruitmentService)
    {
        $this->recruitmentService = $recruitmentService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_posting_id' => 'required|exists:job_postings,id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'cover_letter' => 'nullable|string',
            'resume_path' => 'nullable|string',
        ]);

        try {
            $application = $this->recruitmentService->applyForJob($validated);

            return response()->json(['status' => 'success', 'data' => $application], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:under_review,rejected,moved_to_interview',
        ]);

        try {
            $application = $this->recruitmentService->updateApplicationStatus($id, $validated['status']);

            return response()->json(['status' => 'success', 'data' => $application]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function interviews($id)
    {
        $application = Application::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $application->interviews()->with('interviewer')->get(),
        ]);
    }
}
