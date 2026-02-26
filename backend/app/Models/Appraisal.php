<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    protected $fillable = [
        'employee_id', 'performance_cycle_id', 'form_id',
        'manager_id', 'status', 'final_score', 'comments',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function performanceCycle()
    {
        return $this->belongsTo(PerformanceCycle::class);
    }

    public function form()
    {
        return $this->belongsTo(AppraisalForm::class, 'form_id');
    }

    public function responses()
    {
        return $this->hasMany(AppraisalResponse::class);
    }
}
