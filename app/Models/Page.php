<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug', 'app_type', 'title', 'content', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const APP_TYPES = ['customer', 'driver', 'common'];
}
