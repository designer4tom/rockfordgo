@extends('layouts.admin')

@section('title', __('admin.create_surge_rule'))
@section('page_title', __('admin.create_surge_rule'))

@section('content')
    <a href="{{ route('admin.surge-pricing.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1 mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
    </a>

    <form method="POST" action="{{ route('admin.surge-pricing.store') }}">
        @csrf
        @include('admin.surge-pricing._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.surge-pricing.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.cancel') }}</a>
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">{{ __('admin.create_rule') }}</button>
        </div>
    </form>
@endsection
