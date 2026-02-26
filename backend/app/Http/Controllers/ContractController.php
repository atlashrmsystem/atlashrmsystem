<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Employee;
use App\Services\ContractService;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    protected ContractService $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Contract::with('employee')->latest()->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        try {
            $contract = $this->contractService->createFixedTermContract(
                $employee,
                $request->start_date,
                $request->end_date
            );

            // Generate the Document immediately
            $this->contractService->generateContractPdf($contract);

            return response()->json($contract, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        return response()->json(Contract::with('employee')->findOrFail($id));
    }
}
