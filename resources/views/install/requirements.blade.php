@extends('install.layout', ['step' => 1])

@section('step')
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Server Requirements</h2>
    <p class="text-sm text-gray-500 mb-5">Make sure your server meets these requirements.</p>

    <ul class="divide-y divide-gray-100 mb-6">
        @foreach ($checks as $label => $ok)
            <li class="flex items-center justify-between py-2.5 text-sm">
                <span class="text-gray-700">{{ $label }}</span>
                @if ($ok)
                    <span class="inline-flex items-center gap-1 text-green-600 font-medium">✓ OK</span>
                @else
                    <span class="inline-flex items-center gap-1 text-red-600 font-medium">✕ Missing</span>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($ready)
        <a href="{{ route('install.database') }}" class="block text-center rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Continue →</a>
    @else
        <p class="text-sm text-red-600 text-center">Please fix the missing requirements, then refresh this page.</p>
    @endif
@endsection
