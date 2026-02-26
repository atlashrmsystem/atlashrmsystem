<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingChecklist extends Model
{
    protected $fillable = [
        'name', 'is_mandatory', 'order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];
}
