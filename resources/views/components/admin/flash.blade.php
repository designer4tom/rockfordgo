{{-- Session success/error/warning are shown as toasts (x-admin.toast).
     This component renders inline validation errors only. --}}
@if (isset($errors) && $errors->any() && ! session('error'))
    <div x-data="{ show: true }" x-show="show"
         class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
