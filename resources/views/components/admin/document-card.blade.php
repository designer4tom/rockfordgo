@props(['document', 'driverId'])

@php
    $statusColors = [
        'pending' => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400',
        'approved' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400',
        'rejected' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400',
    ];
    $expired = $document->expiry_date && $document->expiry_date->isPast();
@endphp

<div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4"
    x-data="{
        rejecting: false,
        busy: false,
        status: @js($document->status),
        reason: @js($document->rejection_reason),
        error: '',
        colors: @js($statusColors),
        async submit(url, body = null) {
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        ...(body ? { 'Content-Type': 'application/json' } : {}),
                    },
                    body: body ? JSON.stringify(body) : null,
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.error = data.message || (data.errors?.reason?.[0]) || '{{ __('admin.action_failed') }}';
                    return;
                }
                this.status = data.status;
                if ('reason' in data) this.reason = data.reason;
                this.rejecting = false;
            } catch (e) {
                this.error = '{{ __('admin.network_error') }}';
            } finally {
                this.busy = false;
            }
        },
    }">
    <div class="flex items-center justify-between mb-3">
        <h4 class="font-medium text-gray-800 dark:text-gray-100 capitalize">{{ str_replace('_', ' ', $document->type) }}</h4>
        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
            :class="colors[status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
            x-text="status.charAt(0).toUpperCase() + status.slice(1)">
        </span>
    </div>

    <div class="flex gap-2 mb-3">
        @if ($document->front_image)
            <a href="{{ Storage::url($document->front_image) }}" data-lightbox="doc-{{ $document->id }}" class="block">
                <img src="{{ Storage::url($document->front_image) }}" class="w-24 h-24 rounded-lg object-cover border border-gray-200 dark:border-gray-700" alt="front">
            </a>
        @endif
        @if ($document->back_image)
            <a href="{{ Storage::url($document->back_image) }}" data-lightbox="doc-{{ $document->id }}" class="block">
                <img src="{{ Storage::url($document->back_image) }}" class="w-24 h-24 rounded-lg object-cover border border-gray-200 dark:border-gray-700" alt="back">
            </a>
        @endif
    </div>

    @if ($document->expiry_date)
        <p class="text-xs {{ $expired ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
            {{ __('admin.expiry') }}: {{ $document->expiry_date->format('d M Y') }} {{ $expired ? '('.__('admin.expired').')' : '' }}
        </p>
    @endif

    <p class="mt-1 text-xs text-red-600 dark:text-red-400" x-show="status === 'rejected' && reason" x-cloak>
        {{ __('admin.reason') }}: <span x-text="reason"></span>
    </p>

    <p class="mt-2 text-xs text-red-600 dark:text-red-400" x-show="error" x-cloak x-text="error"></p>

    @if (adminCan('drivers', 'write'))
        <div x-show="status === 'pending'" x-cloak>
            <div class="mt-3 flex gap-2" x-show="!rejecting">
                <button type="button"
                    @click="submit('{{ route('admin.drivers.documents.approve', [$driverId, $document->id]) }}')"
                    :disabled="busy"
                    class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                    <span x-text="busy ? '{{ __('admin.working') }}' : '{{ __('admin.approve') }}'"></span>
                </button>
                <button type="button" @click="rejecting = true" :disabled="busy"
                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 disabled:opacity-50">{{ __('admin.reject') }}</button>
            </div>
            <div x-show="rejecting" x-cloak class="mt-3 space-y-2" x-data="{ rejectReason: '' }">
                <textarea x-model="rejectReason" rows="2" required placeholder="{{ __('admin.reject_reason') }}"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"></textarea>
                <div class="flex gap-2">
                    <button type="button"
                        @click="rejectReason.trim() && submit('{{ route('admin.drivers.documents.reject', [$driverId, $document->id]) }}', { reason: rejectReason })"
                        :disabled="busy || !rejectReason.trim()"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 disabled:opacity-50">
                        <span x-text="busy ? '{{ __('admin.working') }}' : '{{ __('admin.confirm_reject') }}'"></span>
                    </button>
                    <button type="button" @click="rejecting = false" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
