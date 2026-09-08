@props(['title' => '', 'open' => true, 'sectionKey' => null])

{{-- Collapsible section header: uppercase label, hairline, chevron. --}}
<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="pt-4 first:pt-1">
    <button type="button" @click="open = !open" :aria-expanded="open"
        class="group flex w-full items-center gap-2 px-3 pb-1.5 text-[10px] font-bold uppercase tracking-[.08em] text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
        <span class="shrink-0">{{ $title }}</span>
        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
        <svg class="h-3.5 w-3.5 shrink-0 opacity-70 transition-transform duration-200" :class="open || '-rotate-90 rtl:rotate-90'"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M19 15l-7-7-7 7"/>
        </svg>
    </button>

    <div x-show="open" x-collapse x-cloak>
        <div class="space-y-0.5" @if ($sectionKey) data-nav-section="{{ $sectionKey }}" @endif>
            {{ $slot }}
        </div>
    </div>
</div>
