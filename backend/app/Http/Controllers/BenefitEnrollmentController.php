<?php

namespace App\Http\Controllers;

use App\Models\BenefitEnrollment;
use App\Services\BenefitsService;
use Exception;
use Illuminate\Http\Request;

class BenefitEnrollmentController extends Controller
{
    protected $benefitsService;

    public function __construct(BenefitsService $benefitsService)
    {
        $this->benefitsService = $benefitsService;
    }

    public function index(Request $request)
    {
        $query = BenefitEnrollment::with(['employee', 'benefitType']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('benefit_type_id')) {
            $query->where('benefit_type_id', $request->benefit_type_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'benefit_type_id' => 'required|exists:benefit_types,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'coverage_details' => 'nullable|array',
        ]);

        try {
            $enrollment = $this->benefitsService->enrollEmployee($validated);

            return response()->json(['data' => $enrollment->load(['employee', 'benefitType'])], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, BenefitEnrollment $benefitEnrollment)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,cancelled,expired',
            'end_date' => 'nullable|date',
        ]);

        $benefitEnrollment->update($validated);

        return response()->json(['data' => $benefitEnrollment]);
    }
}
