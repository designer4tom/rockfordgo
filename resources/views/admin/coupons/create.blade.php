@extends('layouts.admin')

@section('title', __('admin.new_coupon'))
@section('page_title', __('admin.new_coupon'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.coupons.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('admin.coupons.store') }}" class="max-w-3xl">
        @csrf
        @include('admin.coupons._form', ['submitLabel' => __('admin.create')])
    </form>
@endsection
