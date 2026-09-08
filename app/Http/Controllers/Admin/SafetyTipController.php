<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SafetyTip;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SafetyTipController extends Controller implements HasMiddleware
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
        $tips = SafetyTip::orderBy('sort_order')->paginate(20);

        return view('admin.safety-tips.index', compact('tips'));
    }

    public function create()
    {
        return view('admin.safety-tips.create', ['tip' => new SafetyTip(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['icon'] = $this->storeIcon($request);
        $data['is_active'] = $request->boolean('is_active');

        SafetyTip::create($data);

        return redirect()->route('admin.safety-tips.index')->with('success', 'Safety tip created.');
    }

    public function edit(string $id)
    {
        return view('admin.safety-tips.edit', ['tip' => SafetyTip::findOrFail($id)]);
    }

    public function update(Request $request, string $id)
    {
        $tip = SafetyTip::findOrFail($id);
        $data = $this->validateData($request);

        if ($newIcon = $this->storeIcon($request)) {
            if ($tip->icon) {
                Storage::disk('public')->delete($tip->icon);
            }
            $data['icon'] = $newIcon;
        } else {
            unset($data['icon']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $tip->update($data);

        return redirect()->route('admin.safety-tips.index')->with('success', 'Safety tip updated.');
    }

    public function destroy(string $id)
    {
        $tip = SafetyTip::findOrFail($id);

        if ($tip->icon) {
            Storage::disk('public')->delete($tip->icon);
        }
        $tip->delete();

        return back()->with('success', 'Safety tip deleted.');
    }

    public function toggleStatus(string $id)
    {
        $tip = SafetyTip::findOrFail($id);
        $tip->is_active = ! $tip->is_active;
        $tip->save();

        return back()->with('success', 'Status updated.');
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function storeIcon(Request $request): ?string
    {
        if (! $request->hasFile('icon')) {
            return null;
        }

        $ext = $request->file('icon')->getClientOriginalExtension();

        return $request->file('icon')->storeAs('safety-tips', 'tip-' . Str::random(8) . '.' . $ext, 'public');
    }
}
