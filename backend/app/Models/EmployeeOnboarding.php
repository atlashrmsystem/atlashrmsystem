<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOnboarding extends Model
{
    protected $fillable = [
        'employee_id', 'checklist_item_id', 'completed_at', 'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(OnboardingChecklist::class, 'checklist_item_id');
    }
}
