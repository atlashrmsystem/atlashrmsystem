<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaySlipRequest extends Model
{
    use HasFactory;

    public const WORKFLOW_PENDING_SUPERVISOR = 'pending_supervisor';

    public const WORKFLOW_PENDING_MANAGER = 'pending_manager';

    public const WORKFLOW_PENDING_HR = 'pending_hr';

    public const WORKFLOW_APPROVED = 'approved';

    public const WORKFLOW_REJECTED = 'rejected';

    protected $fillable = [
        'employee_id',
        'store_id',
        'month',
        'status',
        'workflow_status',
        'requested_at',
        'processed_at',
        'supervisor_approved_at',
        'manager_approved_at',
        'hr_approved_at',
        'rejected_at',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'supervisor_approved_at' => 'datetime',
        'manager_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
