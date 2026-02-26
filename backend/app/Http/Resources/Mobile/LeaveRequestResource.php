<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'store_id' => $this->store_id,
            'leave_type' => $this->whenLoaded('type', function () {
                return [
                    'id' => $this->type->id,
                    'name' => $this->type->name,
                ];
            }),
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'total_days' => $this->total_days,
            'status' => $this->status,
            'workflow_status' => $this->workflow_status,
            'reason' => $this->reason,
            'manager_comment' => $this->manager_comment,
            'rejection_reason' => $this->rejection_reason,
            'supervisor_approved_at' => optional($this->supervisor_approved_at)?->toDateTimeString(),
            'manager_approved_at' => optional($this->manager_approved_at)?->toDateTimeString(),
            'hr_approved_at' => optional($this->hr_approved_at)?->toDateTimeString(),
            'rejected_at' => optional($this->rejected_at)?->toDateTimeString(),
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'full_name' => $this->employee->full_name,
                ];
            }),
        ];
    }
}
