@extends('layouts.admin')

@section('title', __('admin.payment_gateways'))
@section('page_title', __('admin.payment_gateways'))

@section('content')
    <x-admin.settings-tabs />

    {{-- Payment method toggles + wallet/limits now live in Business Settings.
         This page manages gateway credentials (MultiPay). Self-contained blade. --}}
    <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-4xl">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.payment_gateways') }}</h3>
        @include('joynala.multi-pay::admin.gateways')
    </section>
@endsection
