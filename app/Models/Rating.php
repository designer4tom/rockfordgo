<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'order_id',
        'rated_by',
        'rater_id',
        'ratee_id',
        'ratee_type',
        'rating',
        'comment',
        'tags',
    ];

    protected $casts = [
        'rating' => 'integer',
        'tags' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
