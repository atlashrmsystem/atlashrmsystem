<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalForm extends Model
{
    protected $fillable = ['name', 'structure'];

    protected $casts = [
        'structure' => 'array',
    ];
}
