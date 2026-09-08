@php($zoneServices = $zoneServices ?? [])

<div class="space-y-6">
    {{-- Section 1: Basic info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.basic_info') }}</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.zone_name') }} <span class="text-red-500">*</span></label>
                <input name="name" type="text" value="{{ old('name', $zone->name) }}" required
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $zone->is_active ?? true))
                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Section 2: Map --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.coverage_area') }}</h3>
            @if ($mapsKey)
                <button type="button" id="zone-clear" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">{{ __('admin.clear_and_redraw') }}</button>
            @endif
        </div>

        @if ($mapsKey)
            {{-- Two ways to define a zone. Drawing suits an irregular area;
                 dropping a pin suits "everything within N km of here", which is
                 most of the time and far quicker. --}}
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button type="button" id="mode-draw" class="px-4 py-2 text-sm font-semibold">{{ __('admin.zone_mode_draw') }}</button>
                    <button type="button" id="mode-pin" class="px-4 py-2 text-sm font-semibold">{{ __('admin.zone_mode_pin') }}</button>
                </div>

                <div id="pin-controls" class="flex flex-wrap items-center gap-2" style="display:none;">
                    <label class="text-sm text-gray-600 dark:text-gray-300">{{ __('admin.zone_radius') }}</label>
                    <input id="pin-radius" type="number" min="0.1" step="0.1" value="{{ old('radius_km', $zone->radius_km ?: 3) }}"
                           class="w-24 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    <span class="text-sm text-gray-500 dark:text-gray-400">km</span>
                    @foreach ([1, 3, 5, 10] as $preset)
                        <button type="button" class="pin-preset rounded-lg border border-gray-300 dark:border-gray-600 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700" data-km="{{ $preset }}">{{ $preset }} km</button>
                    @endforeach
                </div>
            </div>

            <input id="zone-search" type="text" placeholder="{{ __('admin.zone_search_placeholder') }}"
                   class="mb-3 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">

            <div id="zone-map" class="w-full rounded-lg border border-gray-200 dark:border-gray-700" style="height:500px;"></div>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500" id="zone-help">{{ __('admin.draw_polygon_help') }}</p>
            <p class="mt-1 text-xs font-medium text-gray-600 dark:text-gray-300" id="zone-summary"></p>
        @else
            <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-900/40 px-4 py-6 text-center text-sm text-yellow-800 dark:text-yellow-300">
                {{ __('admin.maps_key_missing_zone') }}
            </div>
        @endif

        {{-- Hidden geometry fields --}}
        <input type="hidden" name="polygon" id="polygon_data" value="{{ old('polygon', !empty($zone->polygon) ? json_encode($zone->polygon) : '') }}">
        <input type="hidden" name="center_lat" id="center_lat" value="{{ old('center_lat', $zone->center_lat) }}">
        <input type="hidden" name="center_lng" id="center_lng" value="{{ old('center_lng', $zone->center_lng) }}">
        <input type="hidden" name="radius_km" id="radius_km" value="{{ old('radius_km', $zone->radius_km) }}">
        <input type="hidden" name="shape" id="shape_mode" value="{{ old('shape', $zone->shape ?? 'draw') }}">
        @error('polygon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Section 3: Services in this zone --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.services_in_this_zone') }}</h3>
        <div class="space-y-4">
            @foreach ($services as $service)
                @php($active = array_key_exists($service->id, $zoneServices))
                @php($start = $zoneServices[$service->id]['start'] ?? null)
                @php($end = $zoneServices[$service->id]['end'] ?? null)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4" x-data="{ on: {{ $active ? 'true' : 'false' }}, allTime: {{ ($active && !$start) ? 'true' : 'false' }} }">
                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="services[]" value="{{ $service->id }}" x-model="on"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $service->name }}</span>
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $service->type === 'ride' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' }}">{{ ucfirst($service->type) }}</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" x-show="on">
                            <input type="checkbox" x-model="allTime" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            {{ __('admin.all_time') }}
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-3" x-show="on && !allTime" x-cloak>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.operating_hours_start') }}</label>
                            <input type="time" name="operating_hours[{{ $service->id }}][start]" value="{{ $start ? \Illuminate\Support\Str::substr($start,0,5) : '' }}"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.operating_hours_end') }}</label>
                            <input type="time" name="operating_hours[{{ $service->id }}][end]" value="{{ $end ? \Illuminate\Support\Str::substr($end,0,5) : '' }}"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@if ($mapsKey)
@push('scripts')
<script>
    // Existing geometry (if editing).
    const existingPolygon = @json(!empty($zone->polygon) ? $zone->polygon : null);
    // Falls back to the centre configured in Settings → Map, not a hardcoded
    // city — a new zone should open where this business actually operates.
    @php($mapDefault = mapCenter())
    const existingCenter = { lat: {{ (float) ($zone->center_lat ?: $mapDefault['lat']) }}, lng: {{ (float) ($zone->center_lng ?: $mapDefault['lng']) }} };
    const defaultZoom = {{ $mapDefault['zoom'] }};
    const savedShape = @json($zone->shape ?? null);
    const startRadius = {{ (float) ($zone->radius_km ?? 0) }};

    let map, currentShape = null;

    function el(id) { return document.getElementById(id); }

    function clearShape() {
        if (currentShape) { currentShape.setMap(null); currentShape = null; }
        el('polygon_data').value = '';
        el('radius_km').value = '';
    }

    function persistPolygon(polygon) {
        const coords = [];
        polygon.getPath().forEach(p => coords.push({ lat: p.lat(), lng: p.lng() }));

        // Fewer than 3 points is not an area yet — keep the form empty so it
        // cannot be saved as a degenerate zone.
        if (coords.length < 3) {
            el('polygon_data').value = '';
            el('radius_km').value = '';
            drawSummary(coords.length, false);
            return;
        }

        el('polygon_data').value = JSON.stringify(coords);
        // Centroid as the zone center.
        const lat = coords.reduce((s, c) => s + c.lat, 0) / coords.length;
        const lng = coords.reduce((s, c) => s + c.lng, 0) / coords.length;
        el('center_lat').value = lat.toFixed(8);
        el('center_lng').value = lng.toFixed(8);
        el('radius_km').value = 0;
        drawSummary(coords.length, !drawActive);
    }

    function persistCircle(circle) {
        const c = circle.getCenter();
        el('center_lat').value = c.lat().toFixed(8);
        el('center_lng').value = c.lng().toFixed(8);
        el('radius_km').value = (circle.getRadius() / 1000).toFixed(2);
        // Approximate the circle as polygon points for storage.
        const pts = [];
        for (let i = 0; i < 36; i++) {
            const a = (i / 36) * 2 * Math.PI;
            pts.push({
                lat: c.lat() + (circle.getRadius() / 111320) * Math.cos(a),
                lng: c.lng() + (circle.getRadius() / (111320 * Math.cos(c.lat() * Math.PI / 180))) * Math.sin(a),
            });
        }
        el('polygon_data').value = JSON.stringify(pts);
    }

    // ── Pin & radius mode ───────────────────────────────────────────────────
    // Stores exactly what a drawn circle stored: centre + radius_km, plus a
    // 36-point polygon so zone detection matches precisely, not only by radius.
    let pinMarker = null, pinCircle = null, mode = 'draw';

    function radiusKm() {
        return Math.max(0.1, parseFloat(el('pin-radius').value) || 3);
    }

    function persistPin() {
        if (!pinCircle) return;
        persistCircle(pinCircle);
        const c = pinCircle.getCenter();
        el('zone-summary').textContent = @js(__('admin.zone_selected_summary'))
            .replace(':lat', c.lat().toFixed(5))
            .replace(':lng', c.lng().toFixed(5))
            .replace(':km', radiusKm());
    }

    function placePin(latLng) {
        if (pinMarker) { pinMarker.setMap(null); }
        if (pinCircle) { pinCircle.setMap(null); }

        pinMarker = new google.maps.Marker({ position: latLng, map, draggable: true });
        pinCircle = new google.maps.Circle({
            map, center: latLng, radius: radiusKm() * 1000,
            fillColor: '#1a56db', fillOpacity: 0.2, strokeColor: '#1a56db',
        });

        // Dragging the pin moves the whole zone; no need to redraw.
        pinMarker.addListener('drag', () => pinCircle.setCenter(pinMarker.getPosition()));
        pinMarker.addListener('dragend', persistPin);
        persistPin();
        map.panTo(latLng);
        map.fitBounds(pinCircle.getBounds());
    }

    // ── Draw mode ───────────────────────────────────────────────────────────
    // Google removed DrawingManager in Maps JS v3.65, so vertices are collected
    // by hand: click to add a point, double-click to finish, drag to adjust.
    let drawActive = false;

    function drawSummary(count, finished) {
        if (mode !== 'draw') return;
        const key = finished ? @js(__('admin.zone_draw_done')) : @js(__('admin.zone_draw_points'));
        el('zone-summary').textContent = count ? key.replace(':count', count) : '';
    }

    function newDrawPolygon(path) {
        // The path is wrapped in an outer array deliberately: `paths: []` builds
        // a polygon with NO path at all, and getPath() then returns undefined.
        // `paths: [[]]` gives one empty path that vertices can be pushed into.
        currentShape = new google.maps.Polygon({
            paths: [path || []], editable: true, map,
            fillColor: '#1a56db', fillOpacity: 0.2, strokeColor: '#1a56db',
        });
        const p = currentShape.getPath();
        ['set_at', 'insert_at', 'remove_at'].forEach(ev =>
            p.addListener(ev, () => persistPolygon(currentShape)));
        return currentShape;
    }

    function addVertex(latLng) {
        if (!currentShape) newDrawPolygon([]);
        const path = currentShape.getPath();

        // Finishing with a double-click emits click events of its own, so the
        // last point can arrive twice. Drop a repeat of the previous vertex.
        const last = path.getLength() ? path.getAt(path.getLength() - 1) : null;
        if (last && Math.abs(last.lat() - latLng.lat()) < 1e-9
                 && Math.abs(last.lng() - latLng.lng()) < 1e-9) {
            return;
        }

        path.push(latLng);   // fires insert_at → persistPolygon
    }

    function finishDraw() {
        if (!currentShape || currentShape.getPath().getLength() < 3) return;
        drawActive = false;
        persistPolygon(currentShape);
    }

    function restartDraw() {
        clearShape();
        drawActive = true;
        el('zone-summary').textContent = '';
    }

    function setMode(next) {
        mode = next;
        el('shape_mode').value = next;
        const active = 'bg-indigo-600 text-white';
        const idle = 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';

        el('mode-draw').className = 'px-4 py-2 text-sm font-semibold ' + (next === 'draw' ? active : idle);
        el('mode-pin').className = 'px-4 py-2 text-sm font-semibold ' + (next === 'pin' ? active : idle);
        el('pin-controls').style.display = next === 'pin' ? 'flex' : 'none';
        el('zone-help').textContent = next === 'pin'
            ? @js(__('admin.zone_pin_help'))
            : @js(__('admin.draw_polygon_help'));

        // Only one mode owns the geometry at a time, so switching clears the other.
        if (next === 'pin') {
            drawActive = false;
            if (currentShape) { currentShape.setMap(null); currentShape = null; }
            el('zone-summary').textContent = '';
        } else {
            if (pinMarker) { pinMarker.setMap(null); pinMarker = null; }
            if (pinCircle) { pinCircle.setMap(null); pinCircle = null; }
            restartDraw();
        }
    }

    function initZoneMap() {
        map = new google.maps.Map(el('zone-map'), {
            center: existingCenter, zoom: defaultZoom,
            disableDoubleClickZoom: true,   // double-click finishes a drawn area
        });

        el('mode-draw').addEventListener('click', () => setMode('draw'));
        el('mode-pin').addEventListener('click', () => setMode('pin'));

        map.addListener('click', (e) => {
            if (mode === 'pin') { placePin(e.latLng); return; }
            if (drawActive) addVertex(e.latLng);
        });
        map.addListener('dblclick', () => { if (mode === 'draw') finishDraw(); });

        el('pin-radius').addEventListener('input', () => {
            if (pinCircle) { pinCircle.setRadius(radiusKm() * 1000); persistPin(); }
        });
        document.querySelectorAll('.pin-preset').forEach(btn => {
            btn.addEventListener('click', () => {
                el('pin-radius').value = btn.dataset.km;
                if (pinCircle) { pinCircle.setRadius(radiusKm() * 1000); persistPin(); }
            });
        });

        el('zone-clear').addEventListener('click', () => {
            if (pinMarker) { pinMarker.setMap(null); pinMarker = null; }
            if (pinCircle) { pinCircle.setMap(null); pinCircle = null; }
            restartDraw();
            if (mode === 'pin') el('zone-summary').textContent = '';
        });

        // Restore the saved mode BEFORE the optional extras. Place search is the
        // only thing left that can fail, and a failure there must not leave the
        // form with no mode selected at all — which is what happened while the
        // (now removed) DrawingManager was set up first and threw.
        restoreMode();

        try {
            setupSearch();
        } catch (e) {
            console.error('[zones] place search unavailable', e);
            el('zone-help').textContent = @js(__('admin.zone_map_partial'));
        }
    }

    // Reopen in the mode this zone was created with. A pin zone stores a
    // generated polygon too, so the geometry alone cannot tell them apart.
    function restoreMode() {
        const hasPolygon = existingPolygon && existingPolygon.length;

        if (savedShape === 'pin' || (!savedShape && !hasPolygon && startRadius > 0)) {
            setMode('pin');
            el('pin-radius').value = startRadius > 0 ? startRadius : 3;
            placePin(new google.maps.LatLng(existingCenter.lat, existingCenter.lng));
            return;
        }

        setMode('draw');

        // An existing drawn area reopens finished: drag its points to adjust
        // rather than having stray clicks append new ones.
        if (hasPolygon) {
            newDrawPolygon(existingPolygon);
            drawActive = false;
            const bounds = new google.maps.LatLngBounds();
            existingPolygon.forEach(p => bounds.extend(p));
            map.fitBounds(bounds);
            persistPolygon(currentShape);
        }
    }

    // Search is optional — without it the map still works, you just have to pan
    // to the area yourself.
    function setupSearch() {
        const searchInput = el('zone-search');
        // Enter would otherwise submit the form while picking a suggestion.
        searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });

        if (!(google.maps.places && google.maps.places.Autocomplete)) {
            console.warn('[zones] places library unavailable — search disabled');
            searchInput.placeholder = @js(__('admin.zone_search_unavailable'));
            searchInput.disabled = true;
            return;
        }

        const ac = new google.maps.places.Autocomplete(searchInput, { fields: ['geometry'] });
        ac.bindTo('bounds', map);
        ac.addListener('place_changed', () => {
            const place = ac.getPlace();
            if (!place.geometry) return;
            map.setCenter(place.geometry.location);
            map.setZoom(13);
            if (mode === 'pin') placePin(place.geometry.location);
        });
    }

    window.initZoneMap = initZoneMap;
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initZoneMap"></script>
@endpush
@endif
