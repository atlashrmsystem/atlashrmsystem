<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'total_present_days',
        'total_absent_days',
        'total_late_minutes',
        'total_overtime_minutes',
        'status',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_present_days' => 'integer',
        'total_absent_days' => 'integer',
        'total_late_minutes' => 'integer',
        'total_overtime_minutes' => 'integer',
    ];

    protected $appends = [
        'overtime_hours',
        'lateness_minutes',
        'total_hours',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getOvertimeHoursAttribute(): float
    {
        return round(($this->total_overtime_minutes ?? 0) / 60, 2);
    }

    public function getLatenessMinutesAttribute(): int
    {
        return (int) ($this->total_late_minutes ?? 0);
    }

    public function getTotalHoursAttribute(): float
    {
        // Base 8-hour workday model plus approved overtime.
        $baseHours = (float) (($this->total_present_days ?? 0) * 8);

        return round($baseHours + $this->overtime_hours, 2);
    }
}
