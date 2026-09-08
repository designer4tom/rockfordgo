<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'name',
        'title',
        'language_picture',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // The public layout reads $language->languagePicture (camelCase).
    public function getLanguagePictureAttribute(): ?string
    {
        return $this->attributes['language_picture'] ?? null;
    }
}
