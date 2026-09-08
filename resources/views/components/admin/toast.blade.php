{{-- Session flash messages rendered as auto-dismissing toasts (top-right) --}}
@php($toasts = collect(['success' => 'green', 'error' => 'red', 'warning' => 'amber'])->filter(fn ($c, $type) => session($type)))

@if ($toasts->isNotEmpty())
    <div class="fixed top-4 end-4 z-50 space-y-2 w-80">
        @foreach ($toasts as $type => $color)
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition.opacity.duration.300ms
                 class="flex items-start gap-3 rounded-lg border shadow-lg px-4 py-3 text-sm bg-{{ $color }}-50 border-{{ $color }}-200 text-{{ $color }}-800">
                <span class="flex-1">{{ session($type) }}</span>
                <button type="button" @click="show = false" class="text-{{ $color }}-500 hover:text-{{ $color }}-700">&times;</button>
            </div>
        @endforeach
    </div>
@endif
