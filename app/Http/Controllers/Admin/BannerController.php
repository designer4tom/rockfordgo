<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BannerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['create', 'store', 'edit', 'update', 'toggleStatus']),
            new Middleware('permission:settings,delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $banners = Banner::orderBy('sort_order')->paginate(20);

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create', ['services' => $this->services()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['image'] = $this->storeImage($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['action_value'] = $data['action_type'] === 'none' ? null : ($data['action_value'] ?? null);

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function edit(string $id)
    {
        $banner = Banner::findOrFail($id);

        return view('admin.banners.edit', ['banner' => $banner, 'services' => $this->services()]);
    }

    public function update(Request $request, string $id)
    {
        $banner = Banner::findOrFail($id);
        $data = $this->validateData($request, $banner);

        if ($newImage = $this->storeImage($request)) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $newImage;
        } else {
            unset($data['image']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['action_value'] = $data['action_type'] === 'none' ? null : ($data['action_value'] ?? null);

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated.');
    }

    public function destroy(string $id)
    {
        $banner = Banner::findOrFail($id);

        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return back()->with('success', 'Banner deleted.');
    }

    public function toggleStatus(string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = ! $banner->is_active;
        $banner->save();

        return back()->with('success', 'Status updated.');
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request, ?Banner $banner = null): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => [$banner ? 'nullable' : 'required', 'image', 'max:2048'],
            'button_text' => ['nullable', 'string', 'max:50'],
            'action_type' => ['required', Rule::in(['none', 'url', 'service', 'screen'])],
            'action_value' => ['nullable', 'string', 'max:255', 'required_unless:action_type,none'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    // Store an uploaded banner image and return its relative path (or null).
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $ext = $request->file('image')->getClientOriginalExtension();

        return $request->file('image')->storeAs('banners', 'banner-' . Str::random(8) . '.' . $ext, 'public');
    }

    private function services()
    {
        return Service::orderBy('sort_order')->get(['id', 'name']);
    }
}
