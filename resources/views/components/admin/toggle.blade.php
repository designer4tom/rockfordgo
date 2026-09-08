@props(['route' => '#', 'checked' => false])

{{-- Toggle switch rendered as a tiny POST form --}}
<form method="POST" action="{{ $route }}" class="inline-flex">
    @csrf
    <button type="submit" title="Toggle status"
        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $checked ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}">
        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $checked ? 'translate-x-6' : 'translate-x-1' }}"></span>
    </button>
</form>
