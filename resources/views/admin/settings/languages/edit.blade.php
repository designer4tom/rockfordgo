@extends('layouts.admin')

@section('title', __('admin.edit_translations'))
@section('page_title', __('admin.edit_translations') . ' — ' . $localeName)

@php($canWrite = adminCan('settings', 'write'))
@php($w = $canWrite && ! $locked)

@section('content')
<div class="max-w-4xl" x-data="{ q: '' }">
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('admin.languages.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
        </a>
        <span class="text-xs text-gray-400">{{ count($keys) }} keys · <span class="font-mono">{{ $locale }}</span>@if($isDefault) · {{ __('admin.is_default') }}@endif</span>
    </div>

    @if ($locked)
        <div class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-sm px-4 py-3">
            {{ __('admin.default_language_locked_test_mode') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.languages.update', $locale) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
        @csrf @method('PUT')

        {{-- Search --}}
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <input x-model="q" type="text" placeholder="{{ __('admin.search_keys') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
        </div>

        {{-- Key/value rows --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[60vh] overflow-y-auto">
            @foreach ($keys as $key)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4" x-show="q === '' || '{{ $key }}'.includes(q.toLowerCase()) || @js(strtolower($defaults[$key] ?? '')).includes(q.toLowerCase())">
                    <div>
                        <code class="text-xs text-gray-500 dark:text-gray-400">{{ $key }}</code>
                        @if (! $isDefault && isset($defaults[$key]))
                            <p class="text-xs text-gray-400 mt-1 truncate" title="{{ $defaults[$key] }}">{{ $defaults[$key] }}</p>
                        @endif
                    </div>
                    <input name="translations[{{ $key }}]" value="{{ $values[$key] ?? '' }}" {{ $w ? '' : 'disabled' }}
                        dir="auto" placeholder="{{ $defaults[$key] ?? '' }}"
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                </div>
            @endforeach
        </div>

        @if ($canWrite)
            {{-- Add a new key (also seeded into the default language) --}}
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.add_new_key') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input name="new_key" placeholder="{{ __('admin.new_key') }} (e.g. welcome_message)" {{ $locked ? 'disabled' : '' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm disabled:opacity-50">
                    <input name="new_value" placeholder="{{ __('admin.translation_value') }}" dir="auto" {{ $locked ? 'disabled' : '' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm disabled:opacity-50">
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                <button type="submit" {{ $locked ? 'disabled' : '' }} @if ($locked) title="{{ __('admin.default_language_locked_test_mode') }}" @endif class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">{{ __('admin.save') }}</button>
            </div>
        @endif
    </form>
</div>
@endsection
