<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install · {{ config('app.name', 'ReadyRide') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto py-10 px-4">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'ReadyRide') }}" class="mx-auto h-14 max-w-[240px] object-contain mb-3">
            <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name', 'ReadyRide') }} Installer</h1>
        </div>

        {{-- Step indicator --}}
        @php($steps = ['Requirements', 'Database', 'Migrate', 'Admin', 'Done'])
        <div class="flex items-center justify-between mb-8 text-xs">
            @foreach ($steps as $i => $label)
                <div class="flex-1 flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold
                        {{ $i + 1 <= ($step ?? 1) ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $i + 1 }}</div>
                    <span class="mt-1 {{ $i + 1 <= ($step ?? 1) ? 'text-indigo-600 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <ul class="list-disc ps-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            @yield('step')
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">ReadyRide — guided setup</p>
    </div>
</body>
</html>
