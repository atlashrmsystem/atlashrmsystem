<?php

namespace App\Http\Controllers;

use App\Models\BenefitType;
use Illuminate\Http\Request;

class BenefitTypeController extends Controller
{
    public function index()
    {
        $types = BenefitType::all();

        return response()->json(['data' => $types]);
    }

    public function active()
    {
        $types = BenefitType::where('is_active', true)->get();
        if ($types->isEmpty()) {
            $this->ensureDefaultBenefitTypes();
            $types = BenefitType::where('is_active', true)->get();
        }

        return response()->json(['data' => $types]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:health_insurance,flight_ticket,housing_allowance,other',
            'eligibility_rules' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $benefitType = BenefitType::create($validated);

        return response()->json(['data' => $benefitType], 201);
    }

    private function ensureDefaultBenefitTypes(): void
    {
        BenefitType::updateOrCreate(
            ['name' => 'Enhanced Insurance'],
            [
                'description' => 'Enhanced health insurance coverage for employees.',
                'type' => 'health_insurance',
                'eligibility_rules' => [],
                'is_active' => true,
            ]
        );

        BenefitType::updateOrCreate(
            ['name' => 'Plane Ticket'],
            [
                'description' => 'Annual return plane ticket benefit.',
                'type' => 'flight_ticket',
                'eligibility_rules' => [],
                'is_active' => true,
            ]
        );
    }
}
