@props([
    'action' => null,
    'value' => '',
    // other active filters, kept as hidden inputs so a search doesn't drop them
    'preserve' => [],
    'placeholder' => null,
])

<form method="GET" action="{{ $action }}" class="relative">
    @foreach ($preserve as $key => $val)
        @continue($key === 'search' || $val === null || $val === '')
        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
    @endforeach

    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-400">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
        </svg>
    </span>

    <input type="search" name="search" value="{{ $value }}"
           placeholder="{{ $placeholder ?? __('admin.search') }}"
           class="w-full min-w-[220px] rounded-lg border border-gray-300 py-2.5 pe-3 ps-10 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/12 sm:w-80 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
</form>
