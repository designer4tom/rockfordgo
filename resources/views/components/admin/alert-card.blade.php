@props([
    'title' => '',
    'count' => 0,
    'color' => 'amber',
    'link' => null,
    'id' => null,
])

@php($active = (int) $count > 0)

<{{ $link ? 'a' : 'div' }} @if($link) href="{{ $link }}" @endif
    {{ $attributes->class([
        'dash-card group block rounded-xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800',
        'border-s-4 border-s-'.$color.'-500' => $active,
    ]) }}>
    <p class="text-[13px] font-medium text-gray-500 dark:text-gray-400">{{ $title }}</p>

    <p class="mt-2.5 text-[28px] font-bold leading-none tracking-tight {{ $active ? 'text-'.$color.'-600 dark:text-'.$color.'-400' : 'text-gray-300 dark:text-gray-600' }}"
       @if($id) id="{{ $id }}" @endif>{{ $count }}</p>

    @if ($link)
        <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold {{ $active ? 'text-'.$color.'-600 dark:text-'.$color.'-400' : 'text-gray-400 dark:text-gray-500' }}">
            {{ __('admin.view_all') }}
            <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </span>
    @endif
</{{ $link ? 'a' : 'div' }}>
