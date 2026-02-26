<?php

namespace App\Http\Controllers;

use App\Services\RecruitmentService;
use Illuminate\Http\Request;

class OfferLetterController extends Controller
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
            'offer_date' => 'nullable|date',
            'salary_offered' => 'required|numeric',
            'joining_date' => 'required|date',
            'pdf_path' => 'nullable|string',
        ]);

        try {
            $offer = $this->recruitmentService->createOfferLetter($validated);

            return response()->json(['status' => 'success', 'data' => $offer], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function accept($id)
    {
        try {
            $employee = $this->recruitmentService->acceptOfferLetter($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Offer accepted and employee created successfully.',
                'employee' => $employee,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
