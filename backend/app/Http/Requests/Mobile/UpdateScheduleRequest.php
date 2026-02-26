<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
            'employee_id' => ['sometimes', 'integer', 'exists:employees,id'],
            'shift_id' => ['sometimes', 'integer', 'exists:shifts,id'],
            'date' => ['sometimes', 'date'],
            'is_closing_shift' => ['sometimes', 'boolean'],
        ];
    }
}
