<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledNotification extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'type',
        'target_type',
        'target_id',
        'title',
        'body',
        'data',
        'scheduled_at',
        'sent_at',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
