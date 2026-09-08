@extends('layouts.admin')

@section('title', __('admin.withdrawal_methods'))
@section('page_title', __('admin.withdrawal_methods'))

@php($canWrite = adminCan('payments', 'write'))
@php($canDelete = adminCan('payments', 'delete'))

@section('content')
    <a href="{{ route('admin.payments.withdrawals') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1 mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.withdrawals') }}
    </a>

    @if ($canWrite)
        {{-- Add method --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-5 max-w-3xl">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('admin.add') }} {{ __('admin.withdrawal_methods') }}</h3>
            <form method="POST" action="{{ route('admin.payments.withdrawal-methods.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('admin.code') ?? 'Code' }}</label>
                    <input name="code" required placeholder="bkash" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm font-mono">
                    @error('code')<p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('admin.name') }}</label>
                    <input name="name" required placeholder="bKash" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('admin.instructions') ?? 'Instructions' }}</label>
                    <input name="instructions" placeholder="Enter your bKash account number" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('admin.sort_order') }}</label>
                    <input name="sort_order" type="number" min="0" value="0" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ {{ __('admin.add') }}</button></div>
            </form>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden max-w-3xl">
        @if ($methods->count())
            <table class="rr-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                        <th class="px-4 py-3.5 text-start">Code</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.sort_order') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                        <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                    @foreach ($methods as $m)
                        <tr class="rr-row" x-data="{ editing: false }">
                            <td class="px-4 py-3" x-show="!editing">
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $m->name }}</p>
                                @if ($m->instructions)<p class="text-xs text-gray-400 dark:text-gray-400">{{ $m->instructions }}</p>@endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400" x-show="!editing">{{ $m->code }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-show="!editing">{{ $m->sort_order }}</td>
                            <td class="px-4 py-3" x-show="!editing">
                                @if ($canWrite)
                                    <x-admin.toggle :route="route('admin.payments.withdrawal-methods.toggle-status', $m->id)" :checked="$m->is_active" />
                                @else
                                    <span class="text-xs {{ $m->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $m->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3" x-show="!editing">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($canWrite)<button type="button" @click="editing = true" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.edit') }}</button>@endif
                                    @if ($canDelete)
                                        <form method="POST" action="{{ route('admin.payments.withdrawal-methods.destroy', $m->id) }}" onsubmit="return confirm('{{ __('admin.delete') }}?');">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.delete') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                            {{-- Inline edit row --}}
                            <td colspan="5" class="px-4 py-3" x-show="editing" x-cloak>
                                <form method="POST" action="{{ route('admin.payments.withdrawal-methods.update', $m->id) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                                    @csrf @method('PUT')
                                    <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Code</label><input name="code" value="{{ $m->code }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm font-mono"></div>
                                    <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.name') }}</label><input name="name" value="{{ $m->name }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></div>
                                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.instructions') ?? 'Instructions' }}</label><input name="instructions" value="{{ $m->instructions }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.sort_order') }}</label><input name="sort_order" type="number" min="0" value="{{ $m->sort_order }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></div>
                                    <div class="sm:col-span-4 flex gap-2">
                                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save') }}</button>
                                        <button type="button" @click="editing = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <x-admin.empty-state :message="__('admin.no_data')" />
        @endif
    </div>
@endsection
