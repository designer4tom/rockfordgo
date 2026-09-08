@extends('layouts.admin')

@section('title', __('admin.create_parcel_pricing'))
@section('page_title', __('admin.create_parcel_pricing'))

@section('content')
    <a href="{{ route('admin.parcel-pricing.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 inline-flex items-center gap-1 mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
    </a>

    <form method="POST" action="{{ route('admin.parcel-pricing.store') }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
        @csrf
        @include('admin.parcel-pricing._form')
        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('admin.parcel-pricing.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.cancel') }}</a>
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">{{ __('admin.create') }}</button>
        </div>
    </form>
@endsection
