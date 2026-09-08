<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPageContent;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LandingPageController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,write'),
        ];
    }

    // Single-value sections store one row per field (key => value).
    private const SINGLE_SECTIONS = ['hero', 'app_download', 'contact'];

    // List sections store one row per item, value = JSON of the item fields.
    private const LIST_SECTIONS = ['features', 'stats', 'how_it_works', 'testimonials', 'faq'];

    public function index()
    {
        $single = [];
        foreach (self::SINGLE_SECTIONS as $section) {
            $single[$section] = LandingPageContent::where('section', $section)->pluck('value', 'key')->toArray();
        }

        $lists = [];
        foreach (self::LIST_SECTIONS as $section) {
            $lists[$section] = LandingPageContent::where('section', $section)
                ->orderBy('sort_order')->get()
                ->map(fn ($row) => ['id' => $row->id, 'is_active' => $row->is_active, 'sort_order' => $row->sort_order] + (json_decode($row->value, true) ?: []));
        }

        $seo = $this->settings->getGroup('seo');

        return view('admin.landing-page.index', compact('single', 'lists', 'seo'));
    }

    // Save a single-value section (hero / app_download / contact) or SEO.
    public function update(Request $request, string $section)
    {
        if ($section === 'seo') {
            foreach ($request->except('_token') as $key => $value) {
                $this->settings->set($key, (string) $value, 'seo');
            }

            return $this->ok($request, 'SEO settings saved.');
        }

        abort_unless(in_array($section, self::SINGLE_SECTIONS, true), 404);

        foreach ($request->except('_token') as $key => $value) {
            LandingPageContent::updateOrCreate(
                ['section' => $section, 'key' => $key],
                ['value' => (string) $value]
            );
        }

        return $this->ok($request, ucwords(str_replace('_', ' ', $section)) . ' section saved.');
    }

    public function storeItem(Request $request)
    {
        $section = $request->input('section');
        abort_unless(in_array($section, self::LIST_SECTIONS, true), 404);

        $max = LandingPageContent::where('section', $section)->max('sort_order') ?? 0;
        $fields = $request->except(['_token', 'section']);

        $item = LandingPageContent::create([
            'section' => $section,
            'key' => 'item',
            'value' => json_encode($fields),
            'sort_order' => $max + 1,
            'is_active' => true,
        ]);

        return $this->ok($request, 'Item added.', [
            'item' => ['id' => $item->id, 'is_active' => true, 'sort_order' => $item->sort_order] + $fields,
        ]);
    }

    public function updateItem(Request $request, string $id)
    {
        $item = LandingPageContent::findOrFail($id);
        $fields = $request->except(['_token', '_method', 'section', 'is_active']);

        $item->update([
            'value' => json_encode($fields),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->ok($request, 'Item updated.', [
            'item' => ['id' => $item->id, 'is_active' => $item->is_active, 'sort_order' => $item->sort_order] + $fields,
        ]);
    }

    public function deleteItem(Request $request, string $id)
    {
        LandingPageContent::findOrFail($id)->delete();

        return $this->ok($request, 'Item deleted.');
    }

    // ---------------------------------------------------------------------

    // Return JSON for AJAX requests, otherwise redirect back with a flash.
    private function ok(Request $request, string $message, array $extra = [])
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message] + $extra);
        }

        return back()->with('success', $message);
    }

    // AJAX drag-reorder: { ids: [3,1,2] }.
    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);
        foreach ($ids as $order => $id) {
            LandingPageContent::where('id', $id)->update(['sort_order' => $order + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
