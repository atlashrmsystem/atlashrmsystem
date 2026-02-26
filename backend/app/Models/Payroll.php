<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'basic_salary',
        'allowances',
        'deductions',
        'overtime_pay',
        'net_salary',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'allowances' => 'array',
        'deductions' => 'array',
        'paid_at' => 'datetime',
        'basic_salary' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
