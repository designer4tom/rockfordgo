{{-- Shared by create and edit so the two pages cannot drift apart again.
     $driver is null when adding. --}}
@php($driver = $driver ?? null)
@php($isEdit = $driver && $driver->exists)
@php($vehicle = $isEdit ? ($driver->vehicles->firstWhere('is_active', true) ?? $driver->vehicles->first()) : null)
@php($docs = $isEdit ? $driver->documents->keyBy('type') : collect())

@php($inputCls = 'block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200')
@php($fileCls = 'block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-indigo-700 hover:file:bg-indigo-100')
@php($labelCls = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1')

@if ($errors->any())
    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 p-4 text-sm text-red-700 dark:text-red-300">
        <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- Section 1: Driver info --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.driver_info') }}</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="{{ $labelCls }}">{{ __('admin.name') }} <span class="text-red-500">*</span></label>
            <input name="name" type="text" value="{{ old('name', $driver->name ?? '') }}" required class="{{ $inputCls }}">
            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            {{-- Phone is the driver's login identity, so it stays fixed after creation. --}}
            @if ($isEdit)
                <label class="{{ $labelCls }}">{{ __('admin.phone_read_only') }}</label>
                <input type="text" value="{{ $driver->phone }}" disabled
                       class="block w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400">
            @else
                <label class="{{ $labelCls }}">{{ __('admin.phone') }} <span class="text-red-500">*</span></label>
                <input name="phone" type="text" value="{{ old('phone') }}" required class="{{ $inputCls }}">
                @error('phone') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            @endif
        </div>

        <div>
            <label class="{{ $labelCls }}">{{ __('admin.email') }}</label>
            <input name="email" type="email" value="{{ old('email', $driver->email ?? '') }}" class="{{ $inputCls }}">
            @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $labelCls }}">{{ __('admin.status') }} <span class="text-red-500">*</span></label>
            @php($statuses = ['approved' => __('admin.approved'), 'pending' => __('admin.pending'), 'suspended' => __('admin.suspended')])
            {{-- Keep the driver's own status selectable even when it is one the
                 add form never offers, so saving cannot silently reclassify them. --}}
            @if ($isEdit && ! array_key_exists($driver->status, $statuses))
                @php($statuses[$driver->status] = __('admin.' . $driver->status))
            @endif
            <select name="status" class="{{ $inputCls }}" x-model="status">
                @foreach ($statuses as $val => $label)
                    <option value="{{ $val }}" @selected(old('status', $driver->status ?? 'approved') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        @if ($isEdit)
            {{-- Suspending or blocking is an audited action: it is logged and the
                 driver is notified, exactly as the status panel on their page does. --}}
            <div class="sm:col-span-2" x-show="['suspended','blocked'].includes(status) && status !== @js($driver->status)" x-cloak>
                <label class="{{ $labelCls }}">{{ __('admin.status_change_reason') }} <span class="text-red-500">*</span></label>
                <input name="status_reason" type="text" value="{{ old('status_reason') }}" class="{{ $inputCls }}">
                @error('status_reason') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label class="{{ $labelCls }}">{{ __('admin.zone') }}</label>
            <select name="zone_id" class="{{ $inputCls }}">
                <option value="">— {{ __('admin.none') }} —</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->id }}" @selected(old('zone_id', $driver->zone_id ?? null) == $zone->id)>{{ $zone->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $labelCls }}">{{ __('admin.avatar') }}</label>
            @if ($isEdit && $driver->avatar)
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ asset('storage/' . $driver->avatar) }}" alt="{{ __('admin.current_avatar') }}" class="h-12 w-12 rounded-full object-cover border border-gray-200 dark:border-gray-700">
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.keep_current_file') }}</span>
                </div>
            @endif
            <input name="avatar" type="file" accept="image/*" class="{{ $fileCls }}">
            @error('avatar') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

{{-- Section 2: Vehicle --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('admin.vehicle') }}</h3>
    <p class="text-xs text-gray-400 dark:text-gray-400 mb-4">{{ __('admin.optional') }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="{{ $labelCls }}">{{ __('admin.vehicle_category') }}</label>
            <select name="vehicle_category_id" class="{{ $inputCls }}">
                <option value="">— {{ __('admin.none') }} —</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('vehicle_category_id', optional($vehicle)->vehicle_category_id) == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">{{ __('admin.registration_number') }}</label>
            <input name="vehicle_registration_number" type="text" value="{{ old('vehicle_registration_number', optional($vehicle)->registration_number) }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">{{ __('admin.make') }}</label>
            <input name="vehicle_make" type="text" value="{{ old('vehicle_make', optional($vehicle)->make) }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">{{ __('admin.model') }}</label>
            <input name="vehicle_model" type="text" value="{{ old('vehicle_model', optional($vehicle)->model) }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">{{ __('admin.year') }}</label>
            <input name="vehicle_year" type="text" value="{{ old('vehicle_year', optional($vehicle)->year) }}" placeholder="2022" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">{{ __('admin.color') }}</label>
            <input name="vehicle_color" type="text" value="{{ old('vehicle_color', optional($vehicle)->color) }}" class="{{ $inputCls }}">
        </div>
    </div>
</div>

{{-- Section 3: Documents --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('admin.documents') }}</h3>
    <p class="text-xs text-gray-400 dark:text-gray-400 mb-4">{{ $isEdit ? __('admin.keep_current_file') : __('admin.driver_docs_hint') }}</p>

    @php($badge = ['approved' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300', 'pending' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300', 'rejected' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300'])

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        {{-- Each entry: [type, label, front field, back field or null, expiry field or null] --}}
        @foreach ([
            ['nid', __('admin.nid'), 'nid_front', 'nid_back', null],
            ['driving_license', __('admin.driving_license'), 'license_front', null, 'license_expiry'],
            ['vehicle_registration', __('admin.vehicle_registration'), 'vehicle_registration_doc', null, 'vehicle_registration_expiry'],
            ['vehicle_insurance', __('admin.vehicle_insurance'), 'insurance_doc', null, 'insurance_expiry'],
            ['vehicle_photo', __('admin.vehicle') . ' ' . __('admin.document'), 'vehicle_front_photo', 'vehicle_back_photo', null],
        ] as [$type, $label, $frontField, $backField, $expiryField])
            @php($doc = $docs->get($type))
            <div class="sm:col-span-2 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $label }}</span>
                    @if ($doc)
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$doc->status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ ucfirst($doc->status) }}</span>
                    @elseif ($isEdit)
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.no_document_uploaded') }}</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelCls }}">{{ $backField ? __('admin.front') : __('admin.document') }}</label>
                        @if ($doc && $doc->front_image)
                            <a href="{{ asset('storage/' . $doc->front_image) }}" target="_blank" rel="noopener" class="inline-block mb-2">
                                <img src="{{ asset('storage/' . $doc->front_image) }}" alt="{{ __('admin.current') }}" class="h-16 rounded border border-gray-200 dark:border-gray-700 object-cover">
                            </a>
                        @endif
                        <input name="{{ $frontField }}" type="file" accept="image/*" class="{{ $fileCls }}">
                        @error($frontField) <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    @if ($backField)
                        <div>
                            <label class="{{ $labelCls }}">{{ __('admin.back') }}</label>
                            @if ($doc && $doc->back_image)
                                <a href="{{ asset('storage/' . $doc->back_image) }}" target="_blank" rel="noopener" class="inline-block mb-2">
                                    <img src="{{ asset('storage/' . $doc->back_image) }}" alt="{{ __('admin.current') }}" class="h-16 rounded border border-gray-200 dark:border-gray-700 object-cover">
                                </a>
                            @endif
                            <input name="{{ $backField }}" type="file" accept="image/*" class="{{ $fileCls }}">
                            @error($backField) <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($expiryField)
                        <div>
                            <label class="{{ $labelCls }}">{{ __('admin.expiry_date') }}</label>
                            <input name="{{ $expiryField }}" type="date"
                                   value="{{ old($expiryField, optional(optional($doc)->expiry_date)->format('Y-m-d')) }}"
                                   class="{{ $inputCls }}">
                            @error($expiryField) <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
