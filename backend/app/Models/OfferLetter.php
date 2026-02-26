<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLetter extends Model
{
    protected $fillable = [
        'application_id', 'offer_date', 'salary_offered', 'joining_date', 'status', 'pdf_path',
    ];

    protected $casts = [
        'offer_date' => 'date',
        'joining_date' => 'date',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
