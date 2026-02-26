<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;
        $employeeId = $this->user()?->employee_id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
                Rule::unique('employees', 'email')->ignore($employeeId),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'present_address' => ['sometimes', 'nullable', 'string'],
            'present_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'present_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'permanent_address' => ['sometimes', 'nullable', 'string'],
            'permanent_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'permanent_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
