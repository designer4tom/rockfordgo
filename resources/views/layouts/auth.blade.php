@php($adminLocale = app()->getLocale())
@php($adminRtl = adminIsRtl())
@php($adminDark = request()->cookie('admin_theme', 'light') === 'dark')
<!DOCTYPE html>
<html lang="{{ $adminLocale }}"
      dir="{{ $adminRtl ? 'rtl' : 'ltr' }}"
      class="min-h-full bg-gray-100 dark:bg-gray-900 {{ $adminDark ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Login') — {{ appName() }}</title>
    @include('partials.favicon')
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if ($adminRtl)
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
        <style>body{font-family:'Cairo',sans-serif;}</style>
    @endif
    <style>[x-cloak]{display:none !important;}</style>
    @stack('styles')
</head>
<body class="min-h-full bg-gray-100 dark:bg-gray-900 antialiased">
    {{-- optional full-bleed animated backdrop, rendered behind the centred card --}}
    @yield('backdrop')

    <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full @yield('shell-width', 'max-w-md')">
            @yield('content')
        </div>
    </div>
    @stack('scripts')
</body>
</html>
