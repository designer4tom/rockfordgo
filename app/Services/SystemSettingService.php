<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    // Read a single setting (cached for 1 hour).
    public function get(string $key, $default = null)
    {
        $value = Cache::remember("setting_{$key}", 3600, function () use ($key) {
            return SystemSetting::where('key', $key)->value('value');
        });

        return $value ?? $default;
    }

    // Create or update a setting and bust its caches.
    public function set(string $key, ?string $value, string $group = 'general'): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("setting_{$key}");
        Cache::forget("settings_group_{$group}");
    }

    // All key => value pairs in a group (cached).
    public function getGroup(string $group): array
    {
        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            return SystemSetting::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }

    // Convenience: read a setting as a boolean ("true"/"1" => true).
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), ['true', '1', 'yes', 'on'], true);
    }
}
