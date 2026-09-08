@extends('install.layout', ['step' => 5])

@section('step')
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-3">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900">Installation Complete!</h2>
        <p class="text-sm text-gray-500 mt-1">Your ReadyRide backend is ready.</p>
    </div>

    <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 mb-5 text-sm text-amber-800">
        <p class="font-semibold mb-2">⚠️ Two things must keep running on your server:</p>
        <p class="mb-1"><strong>1. Queue worker</strong> (dispatch, notifications, payments):</p>
        <code class="block bg-amber-100 rounded px-2 py-1 text-xs mb-2">php artisan queue:work --queue=dispatch,broadcasts,default --tries=3</code>
        <p class="mb-1"><strong>2. Scheduler</strong> (cron — scheduled orders, alerts):</p>
        <code class="block bg-amber-100 rounded px-2 py-1 text-xs">* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run >> /dev/null 2>&1</code>
    </div>

    <div class="rounded-lg bg-indigo-50 border border-indigo-200 p-4 mb-6 text-sm text-indigo-800">
        After logging in, open the <strong>Setup Guide</strong> (top of the dashboard) to configure Google Maps, Pusher and Firebase (FCM) — and where to put the keys in the customer &amp; driver apps.
    </div>

    <a href="{{ route('admin.login') }}" class="block text-center rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Go to Admin Login →</a>
@endsection
