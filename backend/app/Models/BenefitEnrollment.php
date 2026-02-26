<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenefitEnrollment extends Model
{
    protected $fillable = [
        'employee_id', 'benefit_type_id', 'start_date',
        'end_date', 'status', 'coverage_details',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'coverage_details' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefitType()
    {
        return $this->belongsTo(BenefitType::class);
    }
}
