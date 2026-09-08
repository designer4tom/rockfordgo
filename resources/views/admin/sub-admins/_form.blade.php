{{--
    Shared sub-admin form.
    Expects: $modules (module => [abilities]), and optionally $admin + $current.
--}}
@php
    $admin = $admin ?? null;
    $current = $current ?? [];
    $selectedRole = old('role', $admin->role ?? 'sub_admin');
    $isActive = old('is_active', $admin->is_active ?? true);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Basic details --}}
    <div class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.name') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $admin->name ?? '') }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.email') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $admin->email ?? '') }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
            @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('admin.password') }} @if(!$admin) <span class="text-red-500 dark:text-red-400">*</span> @else <span class="text-gray-400 dark:text-gray-400 font-normal">({{ __('admin.leave_blank_keep_password') }})</span> @endif
            </label>
            <input id="password" name="password" type="password" {{ $admin ? '' : 'required' }}
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
            @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" {{ $admin ? '' : 'required' }}
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        </div>
    </div>

    {{-- Role + status --}}
    <div class="space-y-4">
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.role') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <select id="role" name="role"
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
                <option value="sub_admin" @selected($selectedRole === 'sub_admin')>{{ __('admin.sub_admin') }}</option>
                <option value="fleet_manager" @selected($selectedRole === 'fleet_manager')>{{ __('admin.fleet_manager') }}</option>
            </select>
            @error('role') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.status') }}</label>
            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($isActive)
                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.active_can_login') }}</span>
            </label>
        </div>
    </div>
</div>

{{-- Permission matrix --}}
<div class="mt-8" x-data="{
    toggleAll(checked) {
        this.$refs.matrix.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = checked);
    },
    toggleColumn(ability, checked) {
        this.$refs.matrix.querySelectorAll('input[data-ability=' + ability + ']').forEach(cb => cb.checked = checked);
    }
}">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.permissions') }}</h3>
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <input type="checkbox" @change="toggleAll($event.target.checked)"
                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
            {{ __('admin.select_all') }}
        </label>
    </div>

    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl" x-ref="matrix">
        <table class="rr-table min-w-full text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-3.5 text-start">{{ __('admin.module') }}</th>
                    @foreach (['read' => __('admin.read'), 'write' => __('admin.write'), 'delete' => __('admin.delete')] as $ability => $label)
                        <th class="px-4 py-3.5 text-center">
                            <div class="flex flex-col items-center gap-1">
                                <span>{{ $label }}</span>
                                <input type="checkbox" @change="toggleColumn('{{ $ability }}', $event.target.checked)"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" title="{{ __('admin.toggle_column') }}">
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                @foreach ($modules as $module => $abilities)
                    <tr class="rr-row">
                        <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $module) }}</td>
                        @foreach (['read', 'write', 'delete'] as $ability)
                            <td class="px-4 py-3 text-center">
                                @if (in_array($ability, $abilities))
                                    <input type="checkbox" name="permissions[{{ $module }}][{{ $ability }}]" value="1"
                                        data-ability="{{ $ability }}"
                                        @checked(old("permissions.$module.$ability", $current[$module][$ability] ?? false))
                                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
