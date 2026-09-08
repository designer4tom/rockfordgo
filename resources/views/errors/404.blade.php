<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found — {{ appName() }}</title>
    @include('partials.favicon')
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-6">
    <div class="text-center">
        <p class="text-7xl font-extrabold text-indigo-600">404</p>
        <h1 class="mt-4 text-xl font-semibold text-gray-900">Page not found</h1>
        <p class="mt-2 text-sm text-gray-500">The page you're looking for doesn't exist or has moved.</p>
        <a href="{{ url()->previous() }}" class="mt-6 inline-block rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Go Back</a>
        <a href="{{ route('admin.dashboard') }}" class="mt-6 ml-2 inline-block rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Dashboard</a>
    </div>
</body>
</html>
