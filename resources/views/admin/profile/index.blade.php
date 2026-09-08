@extends('layouts.admin')

@section('title', __('admin.my_profile'))
@section('page_title', __('admin.my_profile'))

@section('content')    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl">
        {{-- Profile --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.profile_details') }}</h3>
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="flex items-center gap-3 mb-2">
                    @if ($admin->avatar)
                        <img src="{{ Storage::url($admin->avatar) }}" class="w-14 h-14 rounded-full object-cover">
                    @else
                        <div class="w-14 h-14 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xl font-bold">{{ strtoupper(substr($admin->name, 0, 1)) }}</div>
                    @endif
                    <div><p class="font-medium text-gray-800 dark:text-gray-100">{{ $admin->name }}</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $admin->role)) }}</p></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.avatar') }}</label>
                    <input type="file" name="avatar" accept="image/*" class="block w-full text-sm text-gray-500 dark:text-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.name') }}</label>
                    <input name="name" value="{{ old('name', $admin->name) }}" required class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.email') }}</label>
                    <input name="email" type="email" value="{{ old('email', $admin->email) }}" required class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.current_password') }} <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input name="current_password" type="password" required class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 text-sm">
                </div>
                <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.update_profile') }}</button>
            </form>
        </div>

        {{-- Change password + links --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.change_password') }}</h3>
                <form method="POST" action="{{ route('admin.profile.change-password') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.current_password') }}</label>
                        <input name="current_password" type="password" required class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.new_password') }}</label>
                        <input name="password" type="password" required minlength="8" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.confirm_new_password') }}</label>
                        <input name="password_confirmation" type="password" required class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 text-sm">
                    </div>
                    <button class="rounded-lg bg-gray-800 dark:bg-gray-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-900 dark:hover:bg-gray-600">{{ __('admin.change_password') }}</button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('admin.security') }}</h3>
                <div class="flex flex-col gap-2 text-sm">
                    <a href="{{ route('admin.two-factor.setup') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('admin.two_factor_authentication') }} →</a>
                    <a href="{{ route('admin.login-history') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('admin.login_history') }} →</a>
                </div>
            </div>
        </div>
    </div>
@endsection
