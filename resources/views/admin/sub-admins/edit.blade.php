@extends('layouts.admin')

@section('title', __('admin.edit_sub_admin'))
@section('page_title', __('admin.edit_sub_admin'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.sub-admins.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('admin.back_to_list') }}
        </a>
    </div>

    <form method="POST" action="{{ route('admin.sub-admins.update', $admin->id) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
        @csrf
        @method('PUT')
        @include('admin.sub-admins._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('admin.sub-admins.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.cancel') }}</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">{{ __('admin.update_sub_admin') }}</button>
        </div>
    </form>
@endsection
