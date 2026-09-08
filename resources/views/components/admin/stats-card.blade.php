@props([
    'title' => '',
    'value' => '0',
    'subtitle' => null,
    // signed percentage; renders as a coloured up/down pill when provided
    'trend' => null,
    'trendLabel' => null,
    'color' => 'indigo',
    'icon' => null,
    'link' => null,
    'id' => null,
])

@php($up = $trend !== null && (float) $trend >= 0)

<{{ $link ? 'a' : 'div' }} @if($link) href="{{ $link }}" @endif
    {{ $attributes->class(['dash-card block rounded-xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[13px] font-medium text-gray-500 dark:text-gray-400">{{ $title }}</p>
        @if ($icon)
            <span class="dash-ico inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-{{ $color }}-50 text-{{ $color }}-600 dark:bg-{{ $color }}-900/25 dark:text-{{ $color }}-400">
                {{ $icon }}
            </span>
        @endif
    </div>

    <p class="mt-2.5 text-[28px] font-bold leading-none tracking-tight text-gray-900 dark:text-white" @if($id) id="{{ $id }}" @endif>{{ $value }}</p>

    @if ($trend !== null)
        <p class="mt-3 flex items-center gap-1.5 text-xs">
            <span class="inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 font-semibold
                         {{ $up ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-400'
                                : 'bg-red-50 text-red-700 dark:bg-red-900/25 dark:text-red-400' }}">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="{{ $up ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                </svg>
                {{ abs((float) $trend) }}%
            </span>
            <span class="truncate text-gray-400 dark:text-gray-500">{{ $trendLabel }}</span>
        </p>
    @elseif ($subtitle)
        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">{{ $subtitle }}</p>
    @endif
</{{ $link ? 'a' : 'div' }}>
