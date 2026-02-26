<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employee = $this->employee;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->getRoleNames()->values(),
            'stores' => StoreResource::collection($this->stores),
            'employee' => $employee ? [
                'id' => $employee->id,
                'employee_pin' => $employee->employee_pin,
                'full_name' => $employee->full_name,
                'phone' => $employee->phone,
                'department' => $employee->department,
                'job_title' => $employee->job_title,
                'status' => $employee->status,
                'store_id' => $employee->store_id,
            ] : null,
        ];
    }
}
