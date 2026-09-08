<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\TranslationManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LanguageController extends Controller implements HasMiddleware
{
    public function __construct(private TranslationManager $tm)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index', 'edit']),
            new Middleware('permission:settings,write', only: ['store', 'update', 'setDefault', 'destroy']),
        ];
    }

    public function index()
    {
        return view('admin.settings.languages.index', [
            'locales' => $this->tm->locales(),
            'default' => $this->tm->defaultLocale(),
        ]);
    }

    // Add a new language — clones the default language's keys.
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(-[A-Za-z]{2,4})?$/'],
            'name' => ['required', 'string', 'max:50'],
            'rtl' => ['nullable', 'boolean'],
        ]);

        if ($this->tm->exists($data['code'])) {
            return back()->with('error', 'That language code already exists.');
        }

        $this->tm->addLocale($data['code'], $data['name'], $request->boolean('rtl'));

        return redirect()
            ->route('admin.languages.edit', $data['code'])
            ->with('success', $data['name'] . ' added — copied from the default language. Edit the values below.');
    }

    public function setDefault(string $locale)
    {
        if (config('readyride.test_mode')) {
            return back()->with('error', 'Changing the default language is disabled in test mode.');
        }

        if (! $this->tm->exists($locale)) {
            return back()->with('error', 'Unknown language.');
        }
        $this->tm->setDefault($locale);

        return back()->with('success', strtoupper($locale) . ' is now the default language.');
    }

    // Open the key/value editor for a locale.
    public function edit(string $locale)
    {
        if (! $this->tm->exists($locale)) {
            abort(404);
        }

        $defaults = $this->tm->lines($this->tm->defaultLocale());
        $values = $this->tm->lines($locale);
        $keys = $this->tm->editableKeys($locale);
        sort($keys);

        $isDefault = $locale === $this->tm->defaultLocale();

        return view('admin.settings.languages.edit', [
            'locale' => $locale,
            'localeName' => $this->tm->locales()[$locale]['name'] ?? $locale,
            'isDefault' => $isDefault,
            'locked' => $isDefault && config('readyride.test_mode'),
            'keys' => $keys,
            'defaults' => $defaults,
            'values' => $values,
        ]);
    }

    // Save edited key/values (and optionally add a brand-new key for all to translate).
    public function update(Request $request, string $locale)
    {
        if (! $this->tm->exists($locale)) {
            abort(404);
        }

        if ($locale === $this->tm->defaultLocale() && config('readyride.test_mode')) {
            return back()->with('error', 'Editing the default language is disabled in test mode.');
        }

        $data = $request->validate([
            'translations' => ['required', 'array'],
            'translations.*' => ['nullable', 'string'],
            'new_key' => ['nullable', 'string', 'regex:/^[a-z0-9_]+$/'],
            'new_value' => ['nullable', 'string'],
        ]);

        // Merge into the existing stored lines rather than replacing them wholesale —
        // a form with 1000+ keys can exceed PHP's max_input_vars and silently drop
        // fields, which must not delete the keys that never made it into the request.
        $lines = $this->tm->lines($locale);
        foreach ($data['translations'] as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                unset($lines[$key]);
            } else {
                $lines[$key] = $value;
            }
        }

        // Optionally add a new key — seed it into the default locale too so it appears everywhere.
        if (! empty($data['new_key'])) {
            $lines[$data['new_key']] = trim((string) ($data['new_value'] ?? $data['new_key']));

            $default = $this->tm->defaultLocale();
            if ($locale !== $default) {
                $defaultLines = $this->tm->lines($default);
                if (! isset($defaultLines[$data['new_key']])) {
                    $defaultLines[$data['new_key']] = $data['new_key'];
                    $this->tm->writeLines($default, $defaultLines);
                }
            }
        }

        $this->tm->writeLines($locale, $lines);

        return back()->with('success', 'Translations saved.');
    }

    public function destroy(string $locale)
    {
        if ($locale === $this->tm->defaultLocale()) {
            return back()->with('error', 'Cannot delete the default language.');
        }
        $this->tm->deleteLocale($locale);

        return redirect()->route('admin.languages.index')->with('success', 'Language deleted.');
    }
}
