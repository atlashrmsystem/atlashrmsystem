<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryStructure extends Model
{
    protected $table = 'employee_salary_structures';

    protected $fillable = [
        'employee_id',
        'salary_type',
        'total_salary',
        'basic',
        'house_rent',
        'medical',
        'conveyance',
        'deduction_penalty',
        'deduction_others',
        'advance_payment',
        'effective_from',
    ];

    protected $casts = [
        'total_salary' => 'decimal:2',
        'basic' => 'decimal:2',
        'house_rent' => 'decimal:2',
        'medical' => 'decimal:2',
        'conveyance' => 'decimal:2',
        'deduction_penalty' => 'decimal:2',
        'deduction_others' => 'decimal:2',
        'advance_payment' => 'decimal:2',
        'effective_from' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
