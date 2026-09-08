@extends('install.layout', ['step' => 2])

@section('step')
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Database Connection</h2>
    <p class="text-sm text-gray-500 mb-5">Create an empty MySQL database first, then enter its details. We'll test the connection.</p>

    <form method="POST" action="{{ route('install.database.save') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                <input name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                <input name="db_port" value="{{ old('db_port', '3306') }}" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Database name</label>
            <input name="db_database" value="{{ old('db_database') }}" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input name="db_username" value="{{ old('db_username', 'root') }}" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input name="db_password" type="password" value="{{ old('db_password') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">App URL</label>
            <input name="app_url" value="{{ old('app_url', url('/')) }}" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            <p class="mt-1 text-xs text-gray-400">Your public domain, e.g. https://api.yourdomain.com</p>
        </div>

        <button class="block w-full rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Test &amp; Continue →</button>
    </form>
@endsection
