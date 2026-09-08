@extends('install.layout', ['step' => 3])

@section('step')
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Set Up Database</h2>
    <p class="text-sm text-gray-500 mb-5">This creates all tables and loads essential data (services, vehicle categories, settings, pages, FAQs, safety tips).</p>

    <form method="POST" action="{{ route('install.migrate.run') }}">
        @csrf
        <label class="flex items-start gap-2 mb-5 text-sm text-gray-700">
            <input type="checkbox" name="demo_data" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600">
            <span>Also install <strong>demo data</strong> (sample drivers, customers, orders) — useful for testing. Skip for a clean production start.</span>
        </label>

        <button class="block w-full rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700"
            onclick="this.disabled=true;this.innerText='Running… please wait';this.form.submit();">
            Run Setup →
        </button>
        <p class="mt-3 text-xs text-gray-400 text-center">This may take up to a minute. Do not close the page.</p>
    </form>
@endsection
