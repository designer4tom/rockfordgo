@extends('layouts.admin')

@section('title', __('admin.pages'))
@section('page_title', __('admin.pages'))
@section('page_subtitle', __('admin.pages_subtitle'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.pages.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.new_page') }}
            </a>
        @endif
    </div>

    @php($labels = ['customer' => __('admin.customer'), 'driver' => __('admin.driver'), 'common' => __('admin.common')])
    @php($badge = ['customer' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300', 'driver' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300', 'common' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'])

    <div class="space-y-6">
        @forelse ($pages as $type => $group)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge[$type] ?? '' }}">{{ $labels[$type] ?? ucfirst($type) }}</span>
                </div>
                <table class="rr-table min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($group as $page)
                            <tr class="rr-row">
                                <td class="px-6 py-3">
                                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $page->title }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $page->slug }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-xs {{ $page->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $page->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                </td>
                                <td class="px-6 py-3 text-end">
                                    @php($pageUrl = route('page.show', $page->slug))
                                    <div class="flex items-center justify-end gap-3" x-data="{ copied: false }">
                                        {{-- Copy the public URL to paste into Website → Footer · Legal links --}}
                                        <button type="button"
                                            @click="navigator.clipboard.writeText('{{ $pageUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                            class="text-xs font-medium"
                                            :class="copied ? 'text-green-600' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'"
                                            :title="copied ? '{{ __('admin.copied') }}' : '{{ $pageUrl }}'">
                                            <span x-show="!copied">{{ __('admin.copy_url') }}</span>
                                            <span x-show="copied" x-cloak>✓ {{ __('admin.copied') }}</span>
                                        </button>
                                        <a href="{{ $pageUrl }}" target="_blank" rel="noopener"
                                            class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white text-xs font-medium">{{ __('admin.view') }}</a>
                                        @if (adminCan('settings', 'write'))
                                            <a href="{{ route('admin.pages.edit', $page->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        @endif
                                        @if (adminCan('settings', 'delete'))
                                            <form method="POST" action="{{ route('admin.pages.destroy', $page->id) }}" onsubmit="return confirm('{{ __('admin.confirm_delete') }}');">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <x-admin.empty-state :message="__('admin.no_pages_yet')"
                :createRoute="adminCan('settings','write') ? route('admin.pages.create') : null"
                :createLabel="__('admin.new_page')" />
        @endforelse
    </div>
@endsection
