@extends('layouts.admin')

@section('title', __('admin.landing_page'))
@section('page_title', __('admin.landing_page'))

@php($h = fn ($section, $key, $default = '') => $single[$section][$key] ?? $default)
@php($s = fn ($key, $default = '') => $seo[$key] ?? $default)
@php($listFields = [
    'features' => ['icon' => __('admin.icon_emoji'), 'title' => __('admin.title'), 'description' => __('admin.description')],
    'stats' => ['value' => __('admin.stat_value'), 'label' => __('admin.stat_label'), 'icon' => __('admin.icon_emoji')],
    'how_it_works' => ['title' => __('admin.title'), 'description' => __('admin.description'), 'icon' => __('admin.icon')],
    'testimonials' => ['name' => __('admin.customer_name'), 'rating' => __('admin.rating_1_5'), 'review' => __('admin.review')],
    'faq' => ['question' => __('admin.question'), 'answer' => __('admin.answer'), 'category' => __('admin.category')],
])

@section('content')
<div x-data="cms()">
    <div class="flex items-center justify-between mb-5">
        <div class="border-b border-gray-200 dark:border-gray-700 flex-1">
            <nav class="flex flex-wrap gap-1 -mb-px">
                @foreach (['hero' => __('admin.hero'), 'features' => __('admin.features'), 'stats' => __('admin.stats'), 'how_it_works' => __('admin.how_it_works'), 'app_download' => __('admin.app_download'), 'testimonials' => __('admin.testimonials'), 'faq' => __('admin.faq'), 'contact' => __('admin.contact'), 'seo' => __('admin.seo')] as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="px-3 py-2.5 text-sm font-medium border-b-2 transition">{{ $label }}</button>
                @endforeach
            </nav>
        </div>
        <a href="{{ url('/') }}" target="_blank" class="ms-4 rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.preview') }} →</a>
    </div>

    {{-- ===== Single-value sections (AJAX) ===== --}}
    <div x-show="tab === 'hero'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-3xl">
        <form @submit.prevent="saveSection($el)" action="{{ route('admin.landing-page.update', 'hero') }}" class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.hero_badge') }}</label><input name="badge" value="{{ $h('hero', 'badge') }}" placeholder="Your Ride, Your Way" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.headline') }}</label><input name="headline" value="{{ $h('hero', 'headline') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.subheadline') }}</label><textarea name="subheadline" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">{{ $h('hero', 'subheadline') }}</textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.cta_text') }}</label><input name="cta_text" value="{{ $h('hero', 'cta_text') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.cta_link') }}</label><input name="cta_link" value="{{ $h('hero', 'cta_link') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.background_image_url') }}</label><input name="bg_image" value="{{ $h('hero', 'bg_image') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            <div class="flex items-center gap-3"><button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save_hero') }}</button><span x-show="saved" x-cloak class="text-sm text-green-600 dark:text-green-400">{{ __('admin.saved') }} ✓</span></div>
        </form>
    </div>

    <div x-show="tab === 'app_download'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-3xl">
        <form @submit.prevent="saveSection($el)" action="{{ route('admin.landing-page.update', 'app_download') }}" class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.section_title') }}</label><input name="title" value="{{ $h('app_download', 'title') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.description') }}</label><textarea name="description" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">{{ $h('app_download', 'description') }}</textarea></div>
            <div class="grid grid-cols-2 gap-4">
                @foreach (['customer_play' => __('admin.customer_play_store'), 'customer_app' => __('admin.customer_app_store'), 'driver_play' => __('admin.driver_play_store'), 'driver_app' => __('admin.driver_app_store')] as $k => $l)
                    <div><label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $l }}</label><input name="{{ $k }}" value="{{ $h('app_download', $k) }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></div>
                @endforeach
            </div>
            <div class="flex items-center gap-3"><button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save') }}</button><span x-show="saved" x-cloak class="text-sm text-green-600 dark:text-green-400">{{ __('admin.saved') }} ✓</span></div>
        </form>
    </div>

    <div x-show="tab === 'contact'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-3xl">
        <form @submit.prevent="saveSection($el)" action="{{ route('admin.landing-page.update', 'contact') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach (['address' => __('admin.office_address'), 'phone' => __('admin.phone'), 'email' => __('admin.email'), 'facebook' => __('admin.facebook_url'), 'instagram' => __('admin.instagram_url'), 'twitter' => __('admin.twitter_url'), 'linkedin' => __('admin.linkedin_url'), 'whatsapp' => __('admin.whatsapp'), 'map_embed' => __('admin.google_maps_embed_url')] as $k => $l)
                <div class="{{ in_array($k, ['address','map_embed']) ? 'sm:col-span-2' : '' }}">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $l }}</label>
                    <input name="{{ $k }}" value="{{ $h('contact', $k) }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                </div>
            @endforeach
            <div class="sm:col-span-2 flex items-center gap-3"><button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save_contact') }}</button><span x-show="saved" x-cloak class="text-sm text-green-600 dark:text-green-400">{{ __('admin.saved') }} ✓</span></div>
        </form>
    </div>

    <div x-show="tab === 'seo'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-3xl">
        <form @submit.prevent="saveSection($el)" action="{{ route('admin.landing-page.update', 'seo') }}" class="space-y-4" x-data="{ t: '{{ $s('seo_title') }}', d: '{{ $s('seo_description') }}' }">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.page_title') }} <span class="text-xs text-gray-400 dark:text-gray-400">(<span x-text="t.length"></span>/60)</span></label>
                <input name="seo_title" x-model="t" maxlength="60" value="{{ $s('seo_title') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.meta_description') }} <span class="text-xs text-gray-400 dark:text-gray-400">(<span x-text="d.length"></span>/160)</span></label>
                <textarea name="seo_description" x-model="d" maxlength="160" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">{{ $s('seo_description') }}</textarea>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.og_image_url') }}</label><input name="og_image" value="{{ $s('og_image') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.google_analytics_id') }}</label><input name="ga_id" value="{{ $s('ga_id') }}" placeholder="G-XXXXXXXX" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.facebook_pixel_id') }}</label><input name="fb_pixel" value="{{ $s('fb_pixel') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            </div>
            <div class="flex items-center gap-3"><button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save_seo') }}</button><span x-show="saved" x-cloak class="text-sm text-green-600 dark:text-green-400">{{ __('admin.saved') }} ✓</span></div>
        </form>
    </div>

    {{-- ===== List sections (AJAX add / edit / delete / drag-reorder) ===== --}}
    @foreach ($listFields as $section => $fields)
        <div x-show="tab === '{{ $section }}'" x-cloak class="space-y-4 max-w-3xl"
             x-data="listSection('{{ $section }}', {{ Illuminate\Support\Js::from($lists[$section]) }}, {{ Illuminate\Support\Js::from($fields) }})">
            {{-- Add --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('admin.add') }} {{ ucwords(str_replace('_', ' ', $section)) }}</h3>
                <form @submit.prevent="add($el)" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($fields as $field => $label)
                        <div class="{{ in_array($field, ['description','review','answer']) ? 'sm:col-span-2' : '' }}">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                            @if (in_array($field, ['description','review','answer']))
                                <textarea name="{{ $field }}" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></textarea>
                            @else
                                <input name="{{ $field }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                            @endif
                        </div>
                    @endforeach
                    <div class="sm:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ {{ __('admin.add') }}</button></div>
                </form>
            </div>

            {{-- List (drag to reorder) --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <p class="px-4 pt-3 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.drag_rows_to_reorder') }}</p>
                <ul x-ref="list" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="item in items" :key="item.id">
                        <li class="p-4" :data-id="item.id">
                            <div class="flex items-start justify-between gap-3" x-show="editing !== item.id">
                                <div class="flex items-start gap-3 min-w-0">
                                    <span class="cursor-move text-gray-300 dark:text-gray-600 select-none">⠿</span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 dark:text-gray-100" x-text="item['{{ array_key_first($fields) }}'] || '—'"></p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="summary(item)"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs" :class="item.is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-400'" x-text="item.is_active ? '{{ __('admin.active') }}' : '{{ __('admin.hidden') }}'"></span>
                                    <button type="button" @click="editing = item.id" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.edit') }}</button>
                                    <button type="button" @click="remove(item)" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.delete') }}</button>
                                </div>
                            </div>
                            {{-- Inline edit --}}
                            <form x-show="editing === item.id" x-cloak @submit.prevent="update($el, item)" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($fields as $field => $label)
                                    <div class="{{ in_array($field, ['description','review','answer']) ? 'sm:col-span-2' : '' }}">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                                        @if (in_array($field, ['description','review','answer']))
                                            <textarea name="{{ $field }}" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm" x-text="item['{{ $field }}'] || ''"></textarea>
                                        @else
                                            <input name="{{ $field }}" :value="item['{{ $field }}'] || ''" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                                        @endif
                                    </div>
                                @endforeach
                                <label class="sm:col-span-2 inline-flex items-center gap-2 text-sm dark:text-gray-300"><input type="checkbox" name="is_active" value="1" :checked="item.is_active" class="rounded text-indigo-600"> {{ __('admin.active') }}</label>
                                <div class="sm:col-span-2 flex gap-2">
                                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save') }}</button>
                                    <button type="button" @click="editing = null" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                                </div>
                            </form>
                        </li>
                    </template>
                    <li x-show="items.length === 0" class="p-6 text-center text-sm text-gray-400 dark:text-gray-400">{{ __('admin.no_items_yet') }}</li>
                </ul>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const STORE = '{{ route('admin.landing-page.items.store') }}';
    const REORDER = '{{ route('admin.landing-page.items.reorder') }}';
    const ITEM_BASE = '{{ url('admin/landing-page/items') }}';

    function cms() {
        return {
            tab: 'hero',
            saved: false,
            async saveSection(form) {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: new FormData(form),
                });
                if (res.ok) { this.saved = true; setTimeout(() => this.saved = false, 1800); }
            },
        };
    }

    function listSection(section, initial, fields) {
        return {
            section,
            fields,
            items: initial,
            editing: null,
            fieldKeys: Object.keys(fields),
            summary(item) {
                return this.fieldKeys.slice(1).map(k => item[k]).filter(Boolean).join(' · ');
            },
            init() {
                Sortable.create(this.$refs.list, {
                    handle: '.cursor-move', animation: 150,
                    onEnd: () => {
                        const ids = [...this.$refs.list.querySelectorAll('[data-id]')].map(el => el.dataset.id);
                        fetch(REORDER, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }) });
                    },
                });
            },
            async add(form) {
                const body = new FormData(form);
                body.append('section', this.section);
                const res = await fetch(STORE, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
                const data = await res.json();
                if (data.success) { this.items.push(data.item); form.reset(); }
            },
            async update(form, item) {
                const body = new FormData(form);
                body.append('_method', 'PUT');
                const res = await fetch(`${ITEM_BASE}/${item.id}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
                const data = await res.json();
                if (data.success) {
                    const i = this.items.findIndex(x => x.id === item.id);
                    if (i > -1) this.items[i] = data.item;
                    this.editing = null;
                }
            },
            async remove(item) {
                if (!confirm('{{ __('admin.delete_item_confirm') }}')) return;
                const body = new FormData();
                body.append('_method', 'DELETE');
                const res = await fetch(`${ITEM_BASE}/${item.id}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
                if (res.ok) this.items = this.items.filter(x => x.id !== item.id);
            },
        };
    }
</script>
@endpush
