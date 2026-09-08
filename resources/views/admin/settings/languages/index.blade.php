@extends('layouts.admin')

@section('title', __('admin.languages'))
@section('page_title', __('admin.manage_languages'))

@php($w = adminCan('settings', 'write'))
@php($testMode = config('readyride.test_mode'))

@section('content')
    <x-admin.settings-tabs />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
    {{-- Language list --}}
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <h3 class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.languages') }}</h3>
        <table class="rr-table min-w-full text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-3.5 text-start">{{ __('admin.language_name') }}</th>
                    <th class="px-4 py-3.5 text-start">{{ __('admin.language_code') }}</th>
                    <th class="px-4 py-3.5 text-start">{{ __('admin.direction') }}</th>
                    <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                @foreach ($locales as $code => $info)
                    <tr class="rr-row">
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">
                            {{ $info['name'] }}
                            @if ($code === $default)
                                <span class="ms-2 inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs">{{ __('admin.is_default') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400 font-mono">{{ $code }}</td>
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ ($info['rtl'] ?? false) ? 'RTL' : 'LTR' }}</td>
                        <td class="px-6 py-3 text-end">
                            <div class="inline-flex items-center gap-3">
                                @if ($testMode && $code === $default)
                                    <span class="text-gray-400 dark:text-gray-600 text-xs font-medium cursor-not-allowed" title="{{ __('admin.default_language_locked_test_mode') }}">{{ __('admin.edit') }}</span>
                                @else
                                    <a href="{{ route('admin.languages.edit', $code) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.edit') }}</a>
                                @endif
                                @if ($w && $code !== $default)
                                    <form method="POST" action="{{ route('admin.languages.default', $code) }}" class="inline">@csrf<button type="submit" {{ $testMode ? 'disabled' : '' }} @if ($testMode) title="{{ __('admin.set_default_locked_test_mode') }}" @endif class="text-gray-600 dark:text-gray-300 hover:text-gray-900 text-xs font-medium disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:text-gray-600">{{ __('admin.set_default') }}</button></form>
                                    <form method="POST" action="{{ route('admin.languages.destroy', $code) }}" class="inline" onsubmit="return confirm('{{ __('admin.delete_language_confirm') }} {{ $info['name'] }}?')">@csrf @method('DELETE')<button class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('admin.delete') }}</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add language --}}
    @if ($w)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 h-fit">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-1">{{ __('admin.add_language') }}</h3>
            <p class="text-xs text-gray-400 mb-4">{{ __('admin.add_language_help') }}</p>
            <form method="POST" action="{{ route('admin.languages.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.language_code') }}</label>
                    <input name="code" placeholder="{{ __('admin.language_code_placeholder') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.language_name') }}</label>
                    <input name="name" placeholder="{{ __('admin.language_name_placeholder') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="rtl" value="0">
                    <input type="checkbox" name="rtl" value="1" class="rounded text-indigo-600"> {{ __('admin.rtl') }}
                </label>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.add_language') }}</button>
            </form>
        </div>
    @endif
</div>
@endsection
