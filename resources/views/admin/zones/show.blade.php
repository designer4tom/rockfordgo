@extends('layouts.admin')

@section('title', $zone->name)
@section('page_title', __('admin.zone') . ': ' . $zone->name)

@section('content')
    <a href="{{ route('admin.zones.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1 mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
    </a>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.trips_today') }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $tripsToday }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.online_drivers') }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $onlineDrivers->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.status') }}</p>
            <p class="mt-2 text-lg font-semibold {{ $zone->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $zone->is_active ? __('admin.active') : __('admin.inactive') }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.coverage_map') }}</h3>
        @php($hasGeometry = ! empty($zone->polygon) || ($zone->shape === 'pin' && $zone->radius_km > 0))
        @if ($mapsKey && $hasGeometry)
            <div id="zone-show-map" class="w-full rounded-lg border border-gray-200 dark:border-gray-700" style="height:500px;"></div>
            @if ($zone->shape === 'pin' && $zone->radius_km > 0)
                <p class="mt-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                    {{ str_replace([':lat', ':lng', ':km'], [number_format((float) $zone->center_lat, 5), number_format((float) $zone->center_lng, 5), rtrim(rtrim(number_format((float) $zone->radius_km, 2), '0'), '.')], __('admin.zone_selected_summary')) }}
                </p>
            @endif
        @elseif (!$mapsKey)
            <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-900/40 px-4 py-6 text-center text-sm text-yellow-800 dark:text-yellow-300">
                {{ __('admin.maps_key_missing_show') }}
            </div>
        @else
            <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.no_polygon_drawn') }}
            </div>
        @endif
    </div>

    @if ($mapsKey && $hasGeometry)
    @push('scripts')
    <script>
        const zoneShape = @json($zone->shape ?? 'draw');
        const zonePolygon = @json($zone->polygon ?: []);
        const zoneRadiusKm = {{ (float) ($zone->radius_km ?? 0) }};
        @php($mapDefault = mapCenter())
        const zoneCenter = { lat: {{ (float) ($zone->center_lat ?: $mapDefault['lat']) }}, lng: {{ (float) ($zone->center_lng ?: $mapDefault['lng']) }} };
        const onlineDrivers = @json($onlineDrivers);

        function initZoneShowMap() {
            const map = new google.maps.Map(document.getElementById('zone-show-map'), { center: zoneCenter, zoom: 12 });
            const style = { fillColor: '#1a56db', fillOpacity: 0.15, strokeColor: '#1a56db' };
            let bounds = null;

            // A pin zone is drawn as the circle it actually is. Its stored polygon
            // is only a 36-point approximation kept for zone detection, and
            // rendering that instead shows the area as a lumpy blob.
            if (zoneShape === 'pin' && zoneRadiusKm > 0) {
                const circle = new google.maps.Circle(
                    Object.assign({ map, center: zoneCenter, radius: zoneRadiusKm * 1000 }, style));
                new google.maps.Marker({ position: zoneCenter, map });
                bounds = circle.getBounds();
            } else if (zonePolygon.length) {
                new google.maps.Polygon(Object.assign({ paths: [zonePolygon], map }, style));
                bounds = new google.maps.LatLngBounds();
                zonePolygon.forEach(p => bounds.extend(p));
            }

            if (bounds) map.fitBounds(bounds);

            onlineDrivers.forEach(d => {
                if (d.current_lat && d.current_lng) {
                    new google.maps.Marker({ position: { lat: parseFloat(d.current_lat), lng: parseFloat(d.current_lng) }, map, title: d.name });
                }
            });
        }
        window.initZoneShowMap = initZoneShowMap;
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initZoneShowMap"></script>
    @endpush
    @endif
@endsection
