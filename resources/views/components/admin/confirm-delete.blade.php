@props(['action', 'label' => null, 'message' => null])

{{-- Reusable delete button + confirmation modal --}}
<div x-data="{ open: false }" class="inline-block">
    <button type="button" @click="open = true" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ $label ?? __('admin.delete') }}</button>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-sm p-6" @click.outside="open = false">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.confirm_delete') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $message ?? __('admin.confirm_delete_message') }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                <form method="POST" action="{{ $action }}">
                    @csrf @method('DELETE')
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
