<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Services\RecruitmentService;
use Illuminate\Http\Request;

class JobPostingController extends Controller
{
    protected RecruitmentService $recruitmentService;

    public function __construct(RecruitmentService $recruitmentService)
    {
        $this->recruitmentService = $recruitmentService;
    }

    public function index(Request $request)
    {
        $query = JobPosting::with('department');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'location' => 'nullable|string',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'status' => 'in:draft,published,closed',
            'closes_at' => 'nullable|date',
        ]);

        $posting = $this->recruitmentService->createJobPosting($validated, $request->user()->id);

        return response()->json(['status' => 'success', 'data' => $posting], 201);
    }

    public function show($id)
    {
        $posting = JobPosting::with(['department', 'applications'])->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $posting]);
    }

    public function update(Request $request, $id)
    {
        $posting = JobPosting::findOrFail($id);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'department_id' => 'exists:departments,id',
            'location' => 'nullable|string',
            'description' => 'string',
            'requirements' => 'nullable|string',
            'status' => 'in:draft,published,closed',
            'closes_at' => 'nullable|date',
        ]);

        $posting->update($validated);

        return response()->json(['status' => 'success', 'data' => $posting]);
    }

    public function destroy($id)
    {
        $posting = JobPosting::findOrFail($id);
        $posting->update(['status' => 'closed']);

        return response()->json(['status' => 'success', 'message' => 'Job posting closed']);
    }
}
