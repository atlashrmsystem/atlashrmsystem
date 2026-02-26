<?php

namespace App\Services;

use App\Models\BenefitEnrollment;
use App\Models\BenefitType;
use App\Models\Employee;
use Exception;

class BenefitsService
{
    /**
     * Enroll an employee in a benefit, checking eligibility and overlaps.
     */
    public function enrollEmployee(array $data): BenefitEnrollment
    {
        $employee = Employee::with('contracts')->findOrFail($data['employee_id']);
        $benefitType = BenefitType::findOrFail($data['benefit_type_id']);

        if (! $benefitType->is_active) {
            throw new Exception('Benefit type is currently inactive.');
        }

        // 1. Eligibility Check
        $this->verifyEligibility($employee, $benefitType);

        // 2. Overlap Check
        if ($this->hasOverlappingEnrollment($data['employee_id'], $data['benefit_type_id'], $data['start_date'], $data['end_date'] ?? null)) {
            throw new Exception('Employee already has an active or overlapping enrollment for this benefit type.');
        }

        return BenefitEnrollment::create($data);
    }

    private function verifyEligibility(Employee $employee, BenefitType $benefitType)
    {
        $rules = $benefitType->eligibility_rules ?? [];

        if (empty($rules)) {
            return; // No specific rules, everyone is eligible
        }

        // Check contract type rule (e.g. Flight tickets only for expats)
        if (isset($rules['requires_contract_type'])) {
            $requiredType = $rules['requires_contract_type'];
            // Check if employee has any active contract matching the required type
            // Based on start_date and end_date
            $today = now()->format('Y-m-d');
            $hasMatchingContract = $employee->contracts
                ->where('type', $requiredType)
                ->filter(function ($contract) use ($today) {
                    return $contract->start_date <= $today && $contract->end_date >= $today;
                })
                ->isNotEmpty();

            if (! $hasMatchingContract) {
                throw new Exception('Employee does not meet the contract type requirement for this benefit.');
            }
        }
    }

    private function hasOverlappingEnrollment(int $employeeId, int $benefitTypeId, string $startDate, ?string $endDate): bool
    {
        $query = BenefitEnrollment::where('employee_id', $employeeId)
            ->where('benefit_type_id', $benefitTypeId)
            ->whereIn('status', ['active']);

        // Logic to check if date ranges overlap
        $query->where(function ($q) use ($startDate, $endDate) {
            $q->where(function ($subQ) use ($startDate) {
                // New enrollment starts during an existing enrollment
                $subQ->where('start_date', '<=', $startDate)
                    ->where(function ($q2) use ($startDate) {
                        $q2->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                    });
            });

            if ($endDate) {
                $q->orWhere(function ($subQ) use ($endDate) {
                    // New enrollment ends during an existing enrollment
                    $subQ->where('start_date', '<=', $endDate)
                        ->where(function ($q2) use ($endDate) {
                            $q2->whereNull('end_date')->orWhere('end_date', '>=', $endDate);
                        });
                });
                $q->orWhere(function ($subQ) use ($startDate, $endDate) {
                    // New enrollment completely engulfs an existing enrollment
                    $subQ->where('start_date', '>=', $startDate)
                        ->where('end_date', '<=', $endDate);
                });
            } else {
                $q->orWhere('start_date', '>=', $startDate);
            }
        });

        return $query->exists();
    }
}
