<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\PaySlipResource;
use App\Models\Payroll;
use App\Models\PaySlip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaySlipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'super-admin']);

        if (! $user->employee_id && ! $isPrivileged) {
            return PaySlipResource::collection(collect());
        }

        $requestedEmployeeId = $request->integer('employee_id');
        $employeeId = $isPrivileged
            ? ($requestedEmployeeId ?: $user->employee_id)
            : $user->employee_id;

        if (! $employeeId) {
            return PaySlipResource::collection(collect());
        }

        // Keep payslip rows synchronized with generated payroll months.
        Payroll::query()
            ->where('employee_id', $employeeId)
            ->get()
            ->each(function (Payroll $payroll) use ($employeeId): void {
                $month = sprintf('%04d-%02d', $payroll->year, $payroll->month);

                PaySlip::firstOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'month' => $month,
                    ],
                    [
                        'generated_at' => now(),
                    ]
                );
            });

        $paySlips = PaySlip::query()
            ->where('employee_id', $employeeId)
            ->orderByDesc('month')
            ->get();

        return PaySlipResource::collection($paySlips);
    }

    public function download(Request $request, int $id)
    {
        $user = $request->user();
        $paySlip = PaySlip::query()->findOrFail($id);

        if (! $user->hasAnyRole(['admin', 'super-admin']) && $user->employee_id !== $paySlip->employee_id) {
            abort(403, 'Forbidden');
        }

        if ($paySlip->file_path && Storage::disk('local')->exists($paySlip->file_path)) {
            return Storage::disk('local')->download($paySlip->file_path, basename($paySlip->file_path));
        }

        [$year, $month] = explode('-', $paySlip->month);
        $payroll = Payroll::query()
            ->with('employee')
            ->where('employee_id', $paySlip->employee_id)
            ->where('year', (int) $year)
            ->where('month', (int) $month)
            ->first();

        if (! $payroll) {
            abort(404, 'Payroll data not found for this payslip.');
        }

        $html = view('pdf.payslip', [
            'payroll' => $payroll,
            'month' => $paySlip->month,
        ])->render();

        $pdf = Pdf::loadHTML($html);

        $fileName = 'payslip_'.$paySlip->employee_id.'_'.$paySlip->month.'.pdf';
        $path = 'pay_slips/'.$fileName;

        Storage::disk('local')->put($path, $pdf->output());

        $paySlip->update([
            'file_path' => $path,
            'generated_at' => now(),
        ]);

        return Storage::disk('local')->download($path, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
