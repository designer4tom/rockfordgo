@props([
    'title' => '',
    'icon' => null,
    'open' => false,
    'active' => false,
    'tone' => 'text-indigo-500',
])

@php($isActive = $active || $open)

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" {{ $attributes }}>
    <button type="button" @click="open = !open" :aria-expanded="open"
        class="nav-item group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ $isActive
            ? 'text-gray-900 dark:text-white'
            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' }}">
        @if ($icon)
            <span class="nav-ico shrink-0 {{ $tone }}">{{ $icon }}</span>
        @endif
        <span class="flex-1 truncate text-start">{{ $title }}</span>
        <svg class="h-4 w-4 shrink-0 opacity-50 transition-transform duration-200" :class="open && 'rotate-180'"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>
        </svg>
        <span class="nav-grip" aria-hidden="true" title="{{ __('admin.drag_to_reorder') }}">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/></svg>
        </span>
    </button>

    <div x-show="open" x-collapse x-cloak>
        <div class="relative ms-6 mt-0.5 space-y-0.5 ps-3">
            <span class="absolute inset-y-1 start-0 w-px bg-gray-200 dark:bg-gray-700"></span>
            {{ $slot }}
        </div>
    </div>
</div>
