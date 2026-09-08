<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    // Fetch a single setting value with a sane default.
    public static function get(string $key, $default = null)
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    // Create or update a setting value.
    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget('system_settings');
    }

    // All settings keyed by their key (cached).
    public static function all($columns = ['*'])
    {
        return Cache::rememberForever('system_settings', fn () => static::query()->pluck('value', 'key'));
    }
}
