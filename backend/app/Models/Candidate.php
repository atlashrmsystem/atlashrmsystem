<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone',
        'current_company', 'current_position', 'resume_path', 'source', 'status',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
