<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => optional($this->date)->toDateString(),
            'store_id' => $this->store_id,
            'employee_id' => $this->employee_id,
            'shift_id' => $this->shift_id,
            'is_closing_shift' => (bool) $this->is_closing_shift,
            'store' => new StoreResource($this->whenLoaded('store')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'full_name' => $this->employee->full_name,
                    'employee_pin' => $this->employee->employee_pin,
                ];
            }),
        ];
    }
}
