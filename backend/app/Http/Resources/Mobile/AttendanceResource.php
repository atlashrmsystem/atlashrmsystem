<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Emit timezone-aware timestamps so mobile can reliably convert to device local time.
        $clockIn = $this->clock_in_time?->copy()->utc()->toIso8601String();
        $clockOut = $this->clock_out_time?->copy()->utc()->toIso8601String();

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'date' => optional($this->date)->toDateString(),
            // Keep both key styles for mobile compatibility during rollout.
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'clock_in_time' => $clockIn,
            'clock_out_time' => $clockOut,
            'status' => $this->status,
            'store' => new StoreResource($this->whenLoaded('store')),
            'check_in_location' => $this->check_in_location,
            'check_out_location' => $this->check_out_location,
        ];
    }
}
