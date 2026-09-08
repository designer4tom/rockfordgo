@props([
    // GET target for the filter form; defaults to the current path
    'action' => null,
    // link that clears every filter
    'resetUrl' => null,
    // filters currently applied, used for the badge; pass the controller's $filters
    'applied' => [],
])

@php
    $activeCount = collect($applied)->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->count();
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="inline-flex">
    {{-- trigger --}}
    <button type="button" @click="open = true"
        class="relative inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        @if ($activeCount)
            <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[11px] font-bold text-white">{{ $activeCount }}</span>
        @endif
    </button>

    {{-- drawer --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="open = false"></div>

        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full rtl:-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full rtl:-translate-x-full"
             class="absolute inset-y-0 end-0 flex w-full max-w-sm flex-col bg-white shadow-2xl dark:bg-gray-800">

            <form method="GET" action="{{ $action }}" class="flex h-full flex-col">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <h2 class="flex items-center gap-2 text-base font-bold text-gray-900 dark:text-gray-50">
                        {{ __('admin.filters') }}
                        @if ($activeCount)
                            <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-100 px-1.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">{{ $activeCount }}</span>
                        @endif
                    </h2>
                    <button type="button" @click="open = false" aria-label="{{ __('admin.close') }}"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                    {{ $slot }}
                </div>

                <div class="flex shrink-0 gap-3 border-t border-gray-100 px-5 py-4 dark:border-gray-700">
                    @if ($resetUrl)
                        <a href="{{ $resetUrl }}"
                           class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            {{ __('admin.clear_all') }}
                        </a>
                    @endif
                    <button type="submit" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        {{ __('admin.apply_filters') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
