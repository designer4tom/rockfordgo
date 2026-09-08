@props(['active' => collect()])

@if ($active->count())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 dark:bg-gray-800 p-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
            </span>
            <h3 class="font-semibold text-red-700">{{ $active->count() }} {{ __('admin.active_sos') }} {{ __('admin.sos_alerts') }}</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($active as $a)
                <div class="rounded-lg bg-white dark:bg-gray-800 border border-red-100 dark:border-gray-700 p-3">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $a->triggerer_name }}</p>
                        <span class="text-xs text-red-500">{{ $a->created_at->diffForHumans(null, true) }} {{ __('admin.ago') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $a->triggerer_phone ?? '' }}</p>
                    @if ($a->order)<p class="text-xs text-gray-400 mt-1">{{ __('admin.order') }}: {{ $a->order->order_number }}</p>@endif
                    <p class="text-xs text-gray-400 font-mono mt-1">{{ $a->lat }}, {{ $a->lng }}</p>
                    <div class="mt-2 flex gap-2">
                        <a href="{{ route('admin.sos.show', $a->id) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">{{ __('admin.view') }}</a>
                        @if (adminCan('sos', 'write'))
                            <form method="POST" action="{{ route('admin.sos.acknowledge', $a->id) }}">
                                @csrf
                                <button class="text-xs text-amber-600 hover:text-amber-800 font-medium">{{ __('admin.acknowledge') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
