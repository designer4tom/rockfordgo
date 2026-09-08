{{-- Favicon: uses the admin-uploaded app_favicon (Settings → General), falling back
     to the bundled /favicon.ico. rescue() keeps error pages rendering even if the
     database is unavailable. Uploads get a new filename, so no cache-busting needed. --}}
@php($appFavicon = rescue(fn () => \App\Models\SystemSetting::get('app_favicon'), null, false))
<link rel="icon" href="{{ $appFavicon ? \Illuminate\Support\Facades\Storage::url($appFavicon) : asset('favicon.ico') }}">
