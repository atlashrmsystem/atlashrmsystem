<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;

class LeaveService
{
    /**
     * Submit a new leave request ensuring overlaps and balances are checked.
     */
    public function requestLeave(Employee $employee, array $data): LeaveRequest
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1; // Inclusive days

        // 1. Prevent overlapping leaves
        $hasOverlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })->exists();

        if ($hasOverlap) {
            throw new Exception('You already have a pending or approved leave during this period.');
        }

        // 2. Balance Check (except for specific non-accruing leaves like Sick which has different rules)
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        // Sick Leave logic is handled by brackets. For Annual, we check balance.
        if ($leaveType->name === 'Annual') {
            $currentYear = Carbon::now()->year;
            $balance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $currentYear)
                ->first();

            if (! $balance || ($balance->balance_days - $balance->used_days) < $totalDays) {
                throw new Exception('Insufficient Annual Leave balance.');
            }
        }

        $attachmentPath = $data['attachment_path'] ?? null;
        if (($data['attachment'] ?? null) instanceof UploadedFile) {
            $attachmentPath = $data['attachment']->store('leave-attachments', 'public');
        }

        // 3. Create Request
        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);
    }

    /**
     * Calculate Sick Leave Brackets based on UAE Law (90 days max per year)
     * 1-15 days: Full Pay
     * 16-45 days: Half Pay
     * 46-90 days: Unpaid
     */
    public function calculateSickLeavePayBracket(Employee $employee, LeaveRequest $newRequest): array
    {
        $currentYear = Carbon::parse($newRequest->start_date)->year;
        $sickLeaveType = LeaveType::where('name', 'Sick')->firstOrFail();

        // Get total approved sick days already taken this year
        $takenSickDays = LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $sickLeaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->sum('total_days');

        $requestDays = $newRequest->total_days;

        $fullPayDays = 0;
        $halfPayDays = 0;
        $unpaidDays = 0;

        for ($i = 1; $i <= $requestDays; $i++) {
            $dayInYear = $takenSickDays + $i;

            if ($dayInYear <= 15) {
                $fullPayDays++;
            } elseif ($dayInYear <= 45) { // 16 to 45 (30 days total)
                $halfPayDays++;
            } elseif ($dayInYear <= 90) { // 46 to 90 (45 days total)
                $unpaidDays++;
            } else {
                throw new Exception('Exceeded 90 days maximum sick leave per year under UAE Law.');
            }
        }

        return [
            'full_pay_days' => $fullPayDays,
            'half_pay_days' => $halfPayDays,
            'unpaid_days' => $unpaidDays,
        ];
    }
}
