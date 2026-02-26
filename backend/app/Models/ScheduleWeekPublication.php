<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleWeekPublication extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_EDITED_AFTER_PUBLISH = 'edited_after_publish';

    protected $fillable = [
        'store_id',
        'week_start',
        'status',
        'published_by_user_id',
        'published_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'published_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
