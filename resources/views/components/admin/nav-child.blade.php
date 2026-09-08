@props(['href' => '#', 'active' => false, 'icon' => null])

<a href="{{ $href }}"
   {{ $attributes->class([
       'nav-child relative flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium',
       'nav-child-active' => $active,
       'text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! $active,
   ]) }}>
    @if ($icon)
        <span class="shrink-0 opacity-70">{{ $icon }}</span>
    @else
        <span class="nav-dot {{ $active ? 'bg-indigo-600 dark:bg-indigo-400' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
