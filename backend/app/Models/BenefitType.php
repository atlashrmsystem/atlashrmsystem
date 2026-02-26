<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenefitType extends Model
{
    protected $fillable = ['name', 'description', 'type', 'eligibility_rules', 'is_active'];

    protected $casts = [
        'eligibility_rules' => 'array',
        'is_active' => 'boolean',
    ];
}
