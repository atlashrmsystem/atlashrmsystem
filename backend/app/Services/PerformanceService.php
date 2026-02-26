<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalResponse;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\PerformanceCycle;
use Exception;
use Illuminate\Support\Facades\DB;

class PerformanceService
{
    /**
     * Create a new goal, ensuring there is an active cycle.
     */
    public function createGoal(array $data): Goal
    {
        $cycle = PerformanceCycle::findOrFail($data['performance_cycle_id']);

        if (! $cycle->is_active) {
            throw new Exception('Goals can only be created within an active performance cycle.');
        }

        return Goal::create($data);
    }

    /**
     * Start an appraisal for an employee
     */
    public function createAppraisal(array $data): Appraisal
    {
        $cycle = PerformanceCycle::findOrFail($data['performance_cycle_id']);
        if (! $cycle->is_active) {
            throw new Exception('Appraisals must belong to an active performance cycle.');
        }

        $employee = Employee::findOrFail($data['employee_id']);

        if (! $employee->manager_id) {
            throw new Exception('Employee must have a designated manager to initiate an appraisal.');
        }

        $data['manager_id'] = $employee->manager_id;
        $data['status'] = 'draft';

        return Appraisal::create($data);
    }

    /**
     * Submit an appraisal with JSON responses
     */
    public function submitAppraisal(int $appraisalId, array $responses): Appraisal
    {
        return DB::transaction(function () use ($appraisalId, $responses) {
            $appraisal = Appraisal::findOrFail($appraisalId);

            if ($appraisal->status !== 'draft' && $appraisal->status !== 'reviewed') {
                throw new Exception('This appraisal cannot be modified.');
            }

            // Save individual responses
            foreach ($responses as $resp) {
                AppraisalResponse::updateOrCreate(
                    [
                        'appraisal_id' => $appraisal->id,
                        'question_id' => $resp['question_id'],
                    ],
                    [
                        'response_text' => $resp['response_text'] ?? null,
                        'score' => $resp['score'] ?? null,
                    ]
                );
            }

            $appraisal->status = 'submitted';
            $appraisal->save();

            return $appraisal;
        });
    }

    /**
     * Manager reviews and finalizes appraisal
     */
    public function reviewAppraisal(int $appraisalId, int $managerId, array $data): Appraisal
    {
        $appraisal = Appraisal::findOrFail($appraisalId);

        if ($appraisal->manager_id !== $managerId) {
            throw new Exception('Only the assigned manager can review this appraisal.');
        }

        if ($appraisal->status !== 'submitted') {
            throw new Exception('Appraisal is not ready for review.');
        }

        $appraisal->update([
            'status' => 'completed',
            'final_score' => $data['final_score'],
            'comments' => $data['comments'] ?? null,
        ]);

        return $appraisal;
    }
}
