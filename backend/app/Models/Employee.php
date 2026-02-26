<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// Added this import for the contracts relationship

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_pin',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'photo_path',
        'gender',
        'status',
        'date_of_birth',
        'nationality',
        'phone',
        'passport_number',
        'passport_issue_date',
        'passport_expiry',
        'visa_status',
        'visa_issue_date',
        'visa_expiry',
        'emirates_id',
        'emirates_id_issue_date',
        'emirates_id_expiry_date',
        'insurance_start_date',
        'insurance_end_date',
        'job_title',
        'department',
        'basic_salary',
        'allowances',
        'joining_date',
        'manager_id',
        'store_id',
        'permanent_address',
        'permanent_city',
        'permanent_country',
        'present_address',
        'present_city',
        'present_country',
        'linkedin_url',
        'facebook_url',
        'x_url',
    ];

    protected $casts = [
        'passport_number' => 'encrypted',
        'basic_salary' => 'encrypted',
        'allowances' => 'array',
        'passport_issue_date' => 'date',
        'passport_expiry' => 'date',
        'visa_issue_date' => 'date',
        'visa_expiry' => 'date',
        'emirates_id_issue_date' => 'date',
        'emirates_id_expiry_date' => 'date',
        'insurance_start_date' => 'date',
        'insurance_end_date' => 'date',
        'joining_date' => 'date',
        'date_of_birth' => 'date',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return asset('storage/'.$this->photo_path);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function salesEntries(): HasMany
    {
        return $this->hasMany(SalesEntry::class);
    }

    public function paySlips(): HasMany
    {
        return $this->hasMany(PaySlip::class);
    }

    public function paySlipRequests(): HasMany
    {
        return $this->hasMany(PaySlipRequest::class);
    }

    public function salaryCertificateRequests(): HasMany
    {
        return $this->hasMany(SalaryCertificateRequest::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(EmployeeExperience::class);
    }

    public function relatives(): HasMany
    {
        return $this->hasMany(EmployeeRelative::class);
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(EmployeeBankAccount::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function salaryStructure(): HasOne
    {
        return $this->hasOne(EmployeeSalaryStructure::class);
    }
}
