<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
        'employee_id', 'performance_cycle_id', 'title',
        'description', 'status', 'weight',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function performanceCycle()
    {
        return $this->belongsTo(PerformanceCycle::class);
    }
}
