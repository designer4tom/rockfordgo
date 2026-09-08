@extends('layouts.admin')

@section('title', __('admin.setup_guide'))
@section('page_title', __('admin.setup_guide'))

@php($status = integrationStatus())
@php($badge = fn ($ok) => $ok
    ? '<span class="inline-flex items-center gap-1 rounded-full bg-green-50 text-green-700 px-2.5 py-0.5 text-xs font-medium dark:bg-green-900/30 dark:text-green-300">✓ Configured</span>'
    : '<span class="inline-flex items-center gap-1 rounded-full bg-red-50 text-red-700 px-2.5 py-0.5 text-xs font-medium dark:bg-red-900/30 dark:text-red-300">✕ Not configured</span>')

@section('content')<div class="max-w-3xl space-y-6">

    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.setup_guide_intro') }}</p>

    {{-- Status overview --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">1. Integrations status</h3>
        <ul class="space-y-3 text-sm">
            <li class="flex items-center justify-between">
                <span class="text-gray-700 dark:text-gray-300">Google Maps</span> {!! $badge($status['google_maps']) !!}
            </li>
            <li class="flex items-center justify-between">
                <span class="text-gray-700 dark:text-gray-300">Pusher (Real-time)</span> {!! $badge($status['pusher']) !!}
            </li>
            <li class="flex items-center justify-between">
                <span class="text-gray-700 dark:text-gray-300">Firebase / FCM (Push)</span> {!! $badge($status['firebase']) !!}
            </li>
        </ul>
    </div>

    {{-- Google Maps --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">2. Google Maps</h3>
        <ol class="list-decimal ps-5 space-y-1.5 text-sm text-gray-600 dark:text-gray-300">
            <li>Google Cloud Console → enable <strong>Maps JavaScript API</strong>, <strong>Geocoding API</strong>, <strong>Directions API</strong>; enable Billing.</li>
            <li>Create an API key.</li>
            <li>Paste it in <a href="{{ route('admin.settings.map') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Settings → Map</a>.</li>
            <li>In the apps: add the same key to the Customer &amp; Driver app map config.</li>
        </ol>
    </div>

    {{-- Pusher --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">3. Pusher (Real-time tracking &amp; order requests)</h3>
        <ol class="list-decimal ps-5 space-y-1.5 text-sm text-gray-600 dark:text-gray-300">
            <li>Create a <strong>Pusher Channels</strong> app at pusher.com; note App ID, Key, Secret and <strong>Cluster</strong>.</li>
            <li>Enter them in <a href="{{ route('admin.settings.notifications') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Settings → Notifications</a>.</li>
            <li>The apps read the Pusher key &amp; cluster automatically from <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">GET /api/v1/config</code> — no need to hardcode in the apps.</li>
            <li><strong>Cluster must match</strong> the app's cluster, or you'll get a 404.</li>
        </ol>
    </div>

    {{-- Firebase --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">4. Firebase / FCM (Push notifications)</h3>
        <ol class="list-decimal ps-5 space-y-1.5 text-sm text-gray-600 dark:text-gray-300">
            <li>Firebase Console → Project Settings → <strong>Service accounts</strong> → Generate new private key (JSON).</li>
            <li>Upload that JSON in <a href="{{ route('admin.settings.notifications') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Settings → Notifications</a> (Firebase Service Account).</li>
            <li>In the apps: add <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">google-services.json</code> (Android) / <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">GoogleService-Info.plist</code> (iOS) from the same Firebase project to the Customer &amp; Driver apps.</li>
        </ol>
    </div>

    {{-- Stripe --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">5. Stripe (Card payments — optional)</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300">Add <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">STRIPE_SECRET</code>, <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">STRIPE_KEY</code> and <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">STRIPE_CURRENCY</code> to your server <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">.env</code>, then run <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">php artisan config:clear</code>.</p>
    </div>

    {{-- Server (must run) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-200 dark:border-amber-800 shadow-sm p-6">
        <h3 class="font-semibold text-amber-700 dark:text-amber-300 mb-2">6. ⚠️ Server processes (must keep running)</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Without these, order dispatch, push and real-time silently stop.</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-1">Queue worker (use Supervisor in production):</p>
        <pre class="bg-gray-900 text-gray-100 rounded-lg p-3 text-xs overflow-x-auto mb-3">php artisan queue:work --queue=dispatch,broadcasts,default --tries=3 --timeout=300</pre>
        <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-1">Scheduler (add this cron):</p>
        <pre class="bg-gray-900 text-gray-100 rounded-lg p-3 text-xs overflow-x-auto">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</pre>
        <p class="text-xs text-gray-400 dark:text-gray-400 mt-2">After any code/.env change run <code>php artisan optimize:clear</code> and restart the worker (<code>php artisan queue:restart</code>).</p>
    </div>

    {{-- App config --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">7. Customer &amp; Driver app configuration</h3>
        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>• <strong>API base URL</strong> → set both apps to <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">{{ rtrim(config('app.url'), '/') }}/api/v1</code></li>
            <li>• <strong>Pusher key/cluster</strong> → apps fetch from <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">/api/v1/config</code> automatically.</li>
            <li>• <strong>Google Maps key</strong> → put in each app's native map config (AndroidManifest / AppDelegate).</li>
            <li>• <strong>Firebase</strong> → add <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">google-services.json</code> / <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">GoogleService-Info.plist</code> to each app.</li>
        </ul>
    </div>

</div>
@endsection
