<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ListPaySlipRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'workflow_status' => ['sometimes', 'string', 'in:pending_supervisor,pending_manager,pending_hr,approved,rejected'],
            'employee_id' => ['sometimes', 'integer', 'exists:employees,id'],
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
            'month' => ['sometimes', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }
}
