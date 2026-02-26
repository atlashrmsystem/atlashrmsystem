<?php

namespace App\Http\Controllers;

use App\Models\PerformanceCycle;
use Illuminate\Http\Request;

class PerformanceCycleController extends Controller
{
    public function index()
    {
        $cycles = PerformanceCycle::orderBy('start_date', 'desc')->get();

        return response()->json(['data' => $cycles]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $cycle = PerformanceCycle::create($validated);

        return response()->json(['data' => $cycle], 201);
    }

    public function active()
    {
        $cycle = PerformanceCycle::where('is_active', true)->first();

        return response()->json(['data' => $cycle]);
    }
}
