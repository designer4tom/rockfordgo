<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'title',
        'body',
        'type',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // Resolve the notifiable model (user, driver, or admin) on demand.
    public function notifiable()
    {
        return match ($this->notifiable_type) {
            'driver' => Driver::find($this->notifiable_id),
            'admin' => Admin::find($this->notifiable_id),
            default => User::find($this->notifiable_id),
        };
    }
}
