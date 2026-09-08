@extends('layouts.admin')

@section('title', __('admin.edit_driver'))
@section('page_title', __('admin.edit_driver') . ': ' . $driver->name)

@section('content')
    <a href="{{ route('admin.drivers.show', $driver->id) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 inline-flex items-center gap-1 mb-5">
        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
    </a>

    <form method="POST" action="{{ route('admin.drivers.update', $driver->id) }}" enctype="multipart/form-data"
          class="space-y-5 max-w-3xl" x-data="{ status: @js(old('status', $driver->status)) }">
        @csrf @method('PUT')

        @include('admin.drivers._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.drivers.show', $driver->id) }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.cancel') }}</a>
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">{{ __('admin.update_driver') }}</button>
        </div>
    </form>
@endsection
