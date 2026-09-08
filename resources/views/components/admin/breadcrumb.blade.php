@props(['items' => []])

{{-- Breadcrumb: pass an array of ['label' => ..., 'url' => ... (optional)] --}}
<nav class="mb-4 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
    <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">{{ __('admin.dashboard') }}</a>
    @foreach ($items as $item)
        <svg class="w-4 h-4 text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @if (! empty($item['url']) && ! $loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-indigo-600">{{ $item['label'] }}</a>
        @else
            <span class="text-gray-800 dark:text-gray-100 font-medium">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
