<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;

class ContractService
{
    /**
     * Create a new contract ensuring UAE Labor Law compliance (max 3 years)
     */
    public function createFixedTermContract(Employee $employee, string $startDate, string $endDate): Contract
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // UAE Labor Law Validation (Max 3 years)
        if ($start->diffInYears($end) > 3 || ($start->diffInYears($end) == 3 && $start->diffInDays($end->copy()->subYears(3)) > 0)) {
            throw new Exception('Contract duration cannot exceed 3 years under UAE Federal Decree-Law No. 33 of 2021.');
        }

        if ($end->isBefore($start)) {
            throw new Exception('End date cannot be before start date.');
        }

        $contract = Contract::create([
            'employee_id' => $employee->id,
            'start_date' => $start,
            'end_date' => $end,
            'type' => 'fixed-term',
        ]);

        return $contract;
    }

    /**
     * Generate the Dual-Language PDF for the Contract
     */
    public function generateContractPdf(Contract $contract): string
    {
        $employee = $contract->employee;

        // Uses a blade view resources/views/pdf/contract.blade.php
        $pdf = Pdf::loadView('pdf.contract', [
            'contract' => $contract,
            'employee' => $employee,
        ]);

        $fileName = 'contracts/contract_'.$contract->id.'.pdf';

        // Save to default disk (local for dev, S3 for prod)
        Storage::put($fileName, $pdf->output());

        $contract->update(['document_path' => $fileName]);

        return $fileName;
    }
}
