<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class PageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['create', 'store', 'edit', 'update']),
            new Middleware('permission:settings,delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        // Grouped by app_type for a clean Customer / Driver / Common layout.
        $pages = Page::orderBy('app_type')->orderBy('slug')->get()->groupBy('app_type');

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create', ['page' => new Page(['app_type' => 'common', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');

        Page::create($data);

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(string $id)
    {
        return view('admin.pages.edit', ['page' => Page::findOrFail($id)]);
    }

    public function update(Request $request, string $id)
    {
        $page = Page::findOrFail($id);
        $data = $this->validateData($request, $page);
        $data['is_active'] = $request->boolean('is_active');

        $page->update($data);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(string $id)
    {
        Page::findOrFail($id)->delete();

        return back()->with('success', 'Page deleted.');
    }

    private function validateData(Request $request, ?Page $page = null): array
    {
        return $request->validate([
            'slug' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('pages')->where(fn ($q) => $q->where('app_type', $request->input('app_type')))->ignore($page?->id),
            ],
            'app_type' => ['required', Rule::in(Page::APP_TYPES)],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'slug.unique' => 'This slug already exists for the selected app type.',
        ]);
    }
}
