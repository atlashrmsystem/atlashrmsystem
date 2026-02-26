<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string',
            'current_company' => 'nullable|string',
            'current_position' => 'nullable|string',
            'resume_path' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        $candidate = Candidate::create($validated);

        return response()->json(['status' => 'success', 'data' => $candidate], 201);
    }

    public function show($id)
    {
        $candidate = Candidate::with(['applications.jobPosting', 'applications.interviews'])->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $candidate]);
    }

    public function update(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'email' => 'email|unique:candidates,email,'.$id,
            'phone' => 'nullable|string',
            'status' => 'in:new,screened,interviewed,offered,hired,rejected',
        ]);

        $candidate->update($validated);

        return response()->json(['status' => 'success', 'data' => $candidate]);
    }
}
