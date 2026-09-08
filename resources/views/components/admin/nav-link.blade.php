@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
    // per-item icon colour, kept muted while the item is idle
    'tone' => 'text-indigo-500',
])

<a href="{{ $href }}"
   {{ $attributes->class([
       'nav-item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold',
       'nav-item-active' => $active,
       'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' => ! $active,
   ]) }}>
    @if ($icon)
        <span class="nav-ico shrink-0 {{ $active ? 'text-white' : $tone }}">{{ $icon }}</span>
    @endif
    <span class="flex-1 truncate">{{ $slot }}</span>
    {{ $trailing ?? '' }}
    <span class="nav-grip" aria-hidden="true" title="{{ __('admin.drag_to_reorder') }}">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/></svg>
    </span>
</a>
