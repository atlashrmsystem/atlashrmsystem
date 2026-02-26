<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalResponse extends Model
{
    protected $fillable = [
        'appraisal_id', 'question_id', 'response_text', 'score',
    ];

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }
}
