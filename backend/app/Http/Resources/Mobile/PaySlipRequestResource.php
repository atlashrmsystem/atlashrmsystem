<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaySlipRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'store_id' => $this->store_id,
            'month' => $this->month,
            'status' => $this->status,
            'workflow_status' => $this->workflow_status,
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'requested_at' => optional($this->requested_at)?->toDateTimeString(),
            'processed_at' => optional($this->processed_at)?->toDateTimeString(),
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
            'store' => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                ];
            }),
        ];
    }
}
