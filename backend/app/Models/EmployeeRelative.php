<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRelative extends Model
{
    protected $table = 'employee_relatives';

    protected $fillable = [
        'employee_id',
        'name',
        'relationship',
        'phone',
        'address',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
