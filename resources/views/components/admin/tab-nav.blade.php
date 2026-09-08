@props(['tabs' => [], 'current' => '', 'baseUrl' => ''])

{{-- Tab navigation; keeps the active tab in the URL (?tab=...) --}}
<div class="border-b border-gray-200 dark:border-gray-700 mb-6">
    <nav class="flex flex-wrap gap-1 -mb-px">
        @foreach ($tabs as $key => $label)
            <a href="{{ $baseUrl }}?tab={{ $key }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition
                      {{ $current === $key
                          ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                          : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
