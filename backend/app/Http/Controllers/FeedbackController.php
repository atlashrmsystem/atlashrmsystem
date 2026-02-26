<?php

namespace App\Http\Controllers;

use App\Models\FeedbackRequest;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = FeedbackRequest::with(['employee', 'requestedFrom']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('requested_from_id')) {
            $query->where('requested_from_id', $request->requested_from_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'requested_from_id' => 'required|exists:employees,id',
            'context' => 'required|string|max:255',
        ]);

        $feedback = FeedbackRequest::create($validated);

        return response()->json(['data' => $feedback], 201);
    }

    public function update(Request $request, FeedbackRequest $feedback)
    {
        $validated = $request->validate([
            'feedback_text' => 'required|string',
        ]);

        $feedback->update([
            'feedback_text' => $validated['feedback_text'],
            'status' => 'submitted',
        ]);

        return response()->json(['data' => $feedback]);
    }
}
