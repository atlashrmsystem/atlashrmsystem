<?php

namespace App\Http\Controllers;

use App\Models\AppraisalForm;
use Illuminate\Http\Request;

class AppraisalFormController extends Controller
{
    public function index()
    {
        $forms = AppraisalForm::all();

        return response()->json(['data' => $forms]);
    }

    public function show(AppraisalForm $appraisalForm)
    {
        return response()->json(['data' => $appraisalForm]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'structure' => 'required|array',
        ]);

        $form = AppraisalForm::create($validated);

        return response()->json(['data' => $form], 201);
    }
}
