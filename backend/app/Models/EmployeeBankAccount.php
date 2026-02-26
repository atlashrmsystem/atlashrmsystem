<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBankAccount extends Model
{
    protected $table = 'employee_bank_accounts';

    protected $fillable = [
        'employee_id',
        'bank_holder_name',
        'bank_name',
        'branch_name',
        'iban_number',
        'account_number',
        'account_type',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
