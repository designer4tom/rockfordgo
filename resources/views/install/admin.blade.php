@extends('install.layout', ['step' => 4])

@section('step')
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Create Admin Account</h2>
    <p class="text-sm text-gray-500 mb-5">This is your super-admin login for the admin panel.</p>

    <form method="POST" action="{{ route('install.admin.save') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
            <input name="name" value="{{ old('name') }}" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input name="password" type="password" required minlength="8" class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                <input name="password_confirmation" type="password" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            </div>
        </div>

        <button class="block w-full rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Finish Installation →</button>
    </form>
@endsection
