<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Manages editable admin-panel translations stored as JSON under lang/admin/.
 *
 *   lang/admin/languages.json   → { "default": "en", "locales": { "en": {name, rtl}, ... } }
 *   lang/admin/{locale}.json    → flat { "dashboard": "Dashboard", ... }
 *
 * Translations are loaded into Laravel's translator at runtime under the
 * `admin.` namespace, so all views keep using __('admin.key').
 */
class TranslationManager
{
    private string $dir;

    public function __construct()
    {
        $this->dir = base_path('lang/admin');
    }

    // ----- Languages registry -----

    public function meta(): array
    {
        $path = $this->dir . '/languages.json';
        if (! File::exists($path)) {
            return ['default' => 'en', 'locales' => ['en' => ['name' => 'English', 'rtl' => false]]];
        }

        return json_decode(File::get($path), true) ?: ['default' => 'en', 'locales' => []];
    }

    public function locales(): array
    {
        return $this->meta()['locales'] ?? [];
    }

    public function codes(): array
    {
        return array_keys($this->locales());
    }

    public function defaultLocale(): string
    {
        return $this->meta()['default'] ?? 'en';
    }

    public function isRtl(string $locale): bool
    {
        return (bool) ($this->locales()[$locale]['rtl'] ?? false);
    }

    public function exists(string $locale): bool
    {
        return in_array($locale, $this->codes(), true);
    }

    public function setDefault(string $locale): void
    {
        $meta = $this->meta();
        if (isset($meta['locales'][$locale])) {
            $meta['default'] = $locale;
            $this->writeMeta($meta);
        }
    }

    // Add a new language by cloning the default locale's JSON.
    public function addLocale(string $locale, string $name, bool $rtl = false): void
    {
        $meta = $this->meta();
        $meta['locales'][$locale] = ['name' => $name, 'rtl' => $rtl];
        $this->writeMeta($meta);

        // Clone default translations so the new language is ready to edit.
        if (! File::exists($this->file($locale))) {
            $this->writeLines($locale, $this->lines($this->defaultLocale()));
        }
    }

    public function deleteLocale(string $locale): void
    {
        if ($locale === $this->defaultLocale()) {
            return; // never delete the default
        }
        $meta = $this->meta();
        unset($meta['locales'][$locale]);
        $this->writeMeta($meta);
        File::delete($this->file($locale));
    }

    // ----- Translation lines -----

    public function lines(string $locale): array
    {
        $path = $this->file($locale);
        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?: [];
    }

    public function writeLines(string $locale, array $lines): void
    {
        ksort($lines);
        File::put($this->file($locale), json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    }

    // All keys that should exist (union of default + given locale) — for the editor.
    public function editableKeys(string $locale): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->lines($this->defaultLocale())),
            array_keys($this->lines($locale)),
        )));
    }

    // Register a locale's lines into the translator under the `admin.` namespace,
    // with the default locale as a fallback for any missing key.
    public function boot(string $locale): void
    {
        $default = $this->lines($this->defaultLocale());
        $current = $this->lines($locale);
        $merged = array_merge($default, $current); // current overrides default

        $prefixed = [];
        foreach ($merged as $key => $value) {
            $prefixed['admin.' . $key] = $value;
        }

        app('translator')->addLines($prefixed, $locale);
    }

    private function file(string $locale): string
    {
        return $this->dir . '/' . $locale . '.json';
    }

    private function writeMeta(array $meta): void
    {
        File::put($this->dir . '/languages.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
