<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SafetyTip extends Model
{
    protected $fillable = [
        'title', 'description', 'icon', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Bust the public API cache whenever a tip changes.
    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget('api_safety_tips');
        static::saved($forget);
        static::deleted($forget);
    }
}
