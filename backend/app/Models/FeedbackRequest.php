<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackRequest extends Model
{
    protected $fillable = [
        'employee_id', 'requested_from_id', 'context',
        'feedback_text', 'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedFrom()
    {
        return $this->belongsTo(Employee::class, 'requested_from_id');
    }
}
