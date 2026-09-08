@extends('layouts.admin')

@section('title', __('admin.add_customer'))
@section('page_title', __('admin.add_customer'))

@section('content')
    <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1 mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.customers') }}
    </a>

    <form method="POST" action="{{ route('admin.customers.store') }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 max-w-3xl">
        @csrf
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 p-4 text-sm text-red-700 dark:text-red-300">
                <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.name') }} <span class="text-red-500 dark:text-red-400">*</span></label>
                <input name="name" value="{{ old('name') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.phone') }} <span class="text-red-500 dark:text-red-400">*</span></label>
                <input name="phone" value="{{ old('phone') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.email') }}</label>
                <input name="email" type="email" value="{{ old('email') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.opening_balance') }}</label>
                <input name="wallet_balance" type="number" step="0.01" min="0" value="{{ old('wallet_balance', 0) }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.avatar') }}</label>
                <input name="avatar" type="file" accept="image/*" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-4 file:py-2 file:text-indigo-700 dark:file:text-indigo-300">
            </div>
            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
                </label>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('admin.customers.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.cancel') }}</a>
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.add_customer') }}</button>
        </div>
    </form>
@endsection
