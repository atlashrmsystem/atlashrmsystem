<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'store_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'workflow_status',
        'reason',
        'attachment_path',
        'manager_id',
        'manager_comment',
        'supervisor_approved_at',
        'manager_approved_at',
        'hr_approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'float',
        'supervisor_approved_at' => 'datetime',
        'manager_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public const WORKFLOW_PENDING_SUPERVISOR = 'pending_supervisor';

    public const WORKFLOW_PENDING_MANAGER = 'pending_manager';

    public const WORKFLOW_PENDING_HR = 'pending_hr';

    public const WORKFLOW_APPROVED = 'approved';

    public const WORKFLOW_REJECTED = 'rejected';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
