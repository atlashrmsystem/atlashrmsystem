<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryCertificateRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'status' => $this->status,
            'requested_at' => optional($this->requested_at)?->toDateTimeString(),
            'processed_at' => optional($this->processed_at)?->toDateTimeString(),
            'notes' => $this->notes,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'full_name' => $this->employee->full_name,
                ];
            }),
        ];
    }
}
