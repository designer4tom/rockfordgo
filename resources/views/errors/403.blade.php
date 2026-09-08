@extends('layouts.admin')

@section('title', 'Access Denied')
@section('page_title', 'Access Denied')

@section('content')
    <div class="max-w-lg mx-auto mt-10 bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-50 text-red-600 mb-5">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">৪০৩ — Access নেই</h2>
        <p class="mt-3 text-gray-500">
            আপনার এই section-এ access নেই। প্রয়োজন হলে Super Admin-এর সাথে যোগাযোগ করুন।
        </p>
        <p class="mt-1 text-sm text-gray-400">
            {{ $exception->getMessage() ?: 'You do not have permission to access this resource.' }}
        </p>
        <a href="{{ route('admin.dashboard') }}"
           class="mt-6 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Dashboard-এ ফিরে যান
        </a>
    </div>
@endsection
