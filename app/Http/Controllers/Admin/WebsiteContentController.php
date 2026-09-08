<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebsiteContentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['update', 'storeItem', 'updateItem', 'deleteItem', 'reorder']),
        ];
    }

    /**
     * Page → section schema. Drives the admin UI and validates section names.
     * 'single' sections = key⇒value fields; 'list' sections = repeatable items.
     * field def: [label, type] where type = text|textarea|image.
     */
    public static function schema(): array
    {
        return [
            'shared' => [
                'label' => 'Shared (Nav & Footer)',
                'single' => [
                    'nav' => ['label' => 'Navigation', 'fields' => [
                        'brand' => ['Brand name', 'text'],
                        'logo' => ['Logo image', 'image'],
                        'label_home' => ['Nav: Home', 'text'],
                        'label_features' => ['Nav: Features', 'text'],
                        'label_safety' => ['Nav: Safety', 'text'],
                        'label_help' => ['Nav: Help', 'text'],
                        'label_about' => ['Nav: About', 'text'],
                    ]],
                    'footer' => ['label' => 'Footer text', 'fields' => [
                        'tagline' => ['Tagline', 'textarea'],
                        'copyright' => ['Copyright (:year, :brand allowed)', 'text'],
                        'col_company_title' => ['Column 1 title', 'text'],
                        'col_rider_title' => ['Column 2 title', 'text'],
                        'col_driver_title' => ['Column 3 title', 'text'],
                        'col_legal_title' => ['Column 4 title', 'text'],
                    ]],
                    'social' => ['label' => 'Social links', 'fields' => [
                        'facebook' => ['Facebook URL', 'text'],
                        'twitter' => ['Twitter/X URL', 'text'],
                        'instagram' => ['Instagram URL', 'text'],
                        'linkedin' => ['LinkedIn URL', 'text'],
                    ]],
                    'app_download' => ['label' => 'App store links', 'fields' => [
                        'play_url' => ['Google Play URL', 'text'],
                        'app_url' => ['App Store URL', 'text'],
                    ]],
                ],
                'list' => [
                    'footer_col_company' => ['label' => 'Footer · Company links', 'fields' => ['label' => ['Label', 'text'], 'url' => ['URL', 'text']]],
                    'footer_col_rider' => ['label' => 'Footer · Rider links', 'fields' => ['label' => ['Label', 'text'], 'url' => ['URL', 'text']]],
                    'footer_col_driver' => ['label' => 'Footer · Driver links', 'fields' => ['label' => ['Label', 'text'], 'url' => ['URL', 'text']]],
                    'footer_col_legal' => ['label' => 'Footer · Legal links', 'fields' => ['label' => ['Label', 'text'], 'url' => ['URL', 'text']]],
                    'footer_payments' => ['label' => 'Footer · Payment logos', 'fields' => ['image' => ['Logo', 'image'], 'label' => ['Label', 'text']]],
                ],
            ],
            'home' => [
                'label' => 'Home',
                'single' => [
                    'hero' => ['label' => 'Hero', 'fields' => [
                        'badge' => ['Badge pill', 'text'],
                        'title_line1' => ['Headline line 1', 'text'],
                        'title_line2' => ['Headline line 2 (accent)', 'text'],
                        'subtitle' => ['Subtitle', 'textarea'],
                        'phone_light' => ['Phone image (light)', 'image'],
                        'phone_dark' => ['Phone image (dark)', 'image'],
                    ]],
                    'features' => ['label' => 'Features heading', 'fields' => [
                        'title' => ['Title', 'text'],
                        'subtitle' => ['Subtitle', 'text'],
                    ]],
                    'how' => ['label' => 'How It Works heading', 'fields' => [
                        'title' => ['Title', 'text'],
                        'subtitle' => ['Subtitle', 'text'],
                    ]],
                    'cta' => ['label' => 'Download CTA', 'fields' => [
                        'title' => ['Title', 'text'],
                        'subtitle' => ['Subtitle', 'text'],
                    ]],
                ],
                'list' => [
                    'trust_badges' => ['label' => 'Trust badges', 'fields' => ['label' => ['Label', 'text'], 'icon' => ['Icon file (landing-icons)', 'text']]],
                    'features' => ['label' => 'Feature cards', 'fields' => ['title' => ['Title', 'text'], 'copy' => ['Description', 'textarea'], 'icon' => ['Icon file', 'text']]],
                    'stats' => ['label' => 'Stats', 'fields' => ['value' => ['Value', 'text'], 'label' => ['Label', 'text'], 'icon' => ['Icon file', 'text']]],
                    'how' => ['label' => 'Steps', 'fields' => ['title' => ['Title', 'text'], 'copy' => ['Description', 'textarea'], 'icon' => ['Icon file', 'text']]],
                ],
            ],
            'features' => [
                'label' => 'Features',
                'single' => [
                    'hero' => ['label' => 'Hero', 'fields' => [
                        'badge' => ['Badge', 'text'], 'title_line1' => ['Headline line 1', 'text'], 'title_line2' => ['Headline line 2 (accent)', 'text'],
                        'subtitle' => ['Subtitle', 'textarea'], 'image' => ['Hero image', 'image'],
                    ]],
                    'grid_head' => ['label' => 'Grid heading', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'text']]],
                    'cta' => ['label' => 'CTA', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'textarea']]],
                ],
                'list' => [
                    'features' => ['label' => 'Feature cards', 'fields' => ['title' => ['Title', 'text'], 'copy' => ['Description', 'textarea'], 'tone' => ['Tone (rr-tone-purple…)', 'text'], 'icon' => ['Icon name', 'text']]],
                    'stats' => ['label' => 'Stats', 'fields' => ['value' => ['Value', 'text'], 'label' => ['Label', 'text'], 'tone' => ['Tone', 'text'], 'icon' => ['Icon name', 'text']]],
                ],
            ],
            'safety' => [
                'label' => 'Safety',
                'single' => [
                    'hero' => ['label' => 'Hero', 'fields' => [
                        'badge' => ['Badge', 'text'], 'title_line1' => ['Headline line 1', 'text'], 'title_line2' => ['Headline line 2 (accent)', 'text'],
                        'subtitle' => ['Subtitle', 'textarea'], 'image' => ['Hero image', 'image'],
                    ]],
                    'grid_head' => ['label' => 'Features heading', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'text']]],
                    'tips' => ['label' => 'Tips heading', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'text']]],
                    'cta' => ['label' => 'CTA', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'textarea']]],
                ],
                'list' => [
                    'features' => ['label' => 'Safety cards', 'fields' => ['title' => ['Title', 'text'], 'copy' => ['Description', 'textarea'], 'tone' => ['Tone', 'text'], 'icon' => ['Icon name', 'text']]],
                    'stats' => ['label' => 'Stats', 'fields' => ['value' => ['Value', 'text'], 'label' => ['Label', 'text'], 'tone' => ['Tone', 'text'], 'icon' => ['Icon name', 'text']]],
                    'tips' => ['label' => 'Tips', 'fields' => ['tip' => ['Tip', 'text'], 'icon' => ['Icon name', 'text']]],
                ],
            ],
            'help' => [
                'label' => 'Help',
                'single' => [
                    'hero' => ['label' => 'Hero', 'fields' => [
                        'badge' => ['Badge', 'text'], 'title_line1' => ['Headline line 1', 'text'], 'title_line2' => ['Headline line 2 (accent)', 'text'],
                        'subtitle' => ['Subtitle', 'textarea'], 'search_placeholder' => ['Search placeholder', 'text'], 'image' => ['Hero image', 'image'],
                    ]],
                    'topics_head' => ['label' => 'Topics heading', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'text']]],
                    'faq_head' => ['label' => 'FAQ heading', 'fields' => ['title' => ['Title', 'text']]],
                    'support' => ['label' => 'Support heading', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'text'], 'button' => ['Button label', 'text']]],
                    'tips' => ['label' => 'Tips card', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'text'], 'button' => ['Button label', 'text']]],
                ],
                'list' => [
                    'topics' => ['label' => 'Help topics', 'fields' => ['title' => ['Title', 'text'], 'copy' => ['Description', 'textarea'], 'tone' => ['Tone', 'text'], 'icon' => ['Icon name', 'text']]],
                    'faq' => ['label' => 'FAQ', 'fields' => ['question' => ['Question', 'text'], 'answer' => ['Answer', 'textarea']]],
                    'support' => ['label' => 'Support channels', 'fields' => ['title' => ['Title', 'text'], 'copy' => ['Detail', 'text'], 'status' => ['Status badge', 'text'], 'icon' => ['Icon name', 'text']]],
                    'tips' => ['label' => 'Tips list', 'fields' => ['tip' => ['Tip', 'text']]],
                ],
            ],
            'about' => [
                'label' => 'About',
                'single' => [
                    'hero' => ['label' => 'Hero', 'fields' => [
                        'badge' => ['Badge', 'text'], 'title_line1' => ['Headline line 1', 'text'], 'title_line2' => ['Headline line 2 (accent)', 'text'],
                        'subtitle' => ['Subtitle', 'textarea'], 'image' => ['Hero image', 'image'],
                    ]],
                    'mission' => ['label' => 'Mission', 'fields' => ['title' => ['Title', 'text'], 'body' => ['Statement', 'textarea']]],
                    'values_head' => ['label' => 'Values heading', 'fields' => ['title' => ['Title', 'text']]],
                    'team_head' => ['label' => 'Team heading', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'text']]],
                    'cta' => ['label' => 'CTA', 'fields' => ['title' => ['Title', 'text'], 'subtitle' => ['Subtitle', 'textarea']]],
                ],
                'list' => [
                    'values' => ['label' => 'Values', 'fields' => ['title' => ['Title', 'text'], 'text' => ['Description', 'textarea'], 'tone' => ['Tone', 'text'], 'icon' => ['Icon name', 'text']]],
                    'stats' => ['label' => 'Stats', 'fields' => ['value' => ['Value', 'text'], 'label' => ['Label', 'text'], 'tone' => ['Tone', 'text'], 'icon' => ['Icon name', 'text']]],
                    'team' => ['label' => 'Team members', 'fields' => ['name' => ['Name', 'text'], 'role' => ['Role', 'text'], 'img' => ['Photo', 'image']]],
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $schema = self::schema();
        $page = $request->query('page', 'shared');
        abort_unless(isset($schema[$page]), 404);

        $locale = $request->query('locale', 'en');
        $languages = Language::where('is_active', true)->orderBy('sort_order')->get();
        if (! $languages->firstWhere('name', $locale)) {
            $locale = $languages->firstWhere('is_default', true)?->name ?? ($languages->first()->name ?? 'en');
        }

        // Load this page+locale rows.
        $rows = SiteContent::where('page', $page)->where('locale', $locale)->orderBy('sort_order')->get();

        $single = [];   // section => key => value
        $lists = [];    // section => [ {id,is_active,sort_order,...fields} ]
        foreach ($rows as $row) {
            if ($row->key === 'item' || $row->type === 'list') {
                $lists[$row->section][] = ['id' => $row->id, 'is_active' => (bool) $row->is_active, 'sort_order' => $row->sort_order]
                    + (json_decode($row->value, true) ?: []);
            } else {
                $single[$row->section][$row->key] = $row->value;
            }
        }

        return view('admin.website.index', [
            'schema' => $schema,
            'page' => $page,
            'locale' => $locale,
            'languages' => $languages,
            'single' => $single,
            'lists' => $lists,
        ]);
    }

    // Save a single-value section (one row per field). Handles image uploads.
    public function update(Request $request, string $page, string $section)
    {
        $schema = self::schema();
        abort_unless(isset($schema[$page]['single'][$section]), 404);

        $locale = $request->input('_locale', 'en');
        $fields = $schema[$page]['single'][$section]['fields'];

        foreach ($fields as $key => [$label, $type]) {
            if ($type === 'image') {
                if ($request->hasFile($key)) {
                    $this->putValue($page, $locale, $section, $key, 'image', $this->storeImage($request, $key, $page));
                }
                continue; // image not re-uploaded → keep existing
            }
            if ($request->has($key)) {
                $this->putValue($page, $locale, $section, $key, 'text', (string) $request->input($key));
            }
        }

        $this->clearCache();

        return $this->ok($request, ucwords(str_replace('_', ' ', $section)) . ' saved.');
    }

    public function storeItem(Request $request)
    {
        $schema = self::schema();
        $page = $request->input('page');
        $section = $request->input('section');
        abort_unless(isset($schema[$page]['list'][$section]), 404);

        $locale = $request->input('_locale', 'en');
        $fields = $this->collectItemFields($request, $schema[$page]['list'][$section]['fields'], $page);

        $max = SiteContent::where('page', $page)->where('locale', $locale)->where('section', $section)->max('sort_order') ?? 0;

        $item = SiteContent::create([
            'page' => $page, 'locale' => $locale, 'section' => $section,
            'key' => 'item', 'type' => 'list', 'value' => json_encode($fields),
            'sort_order' => $max + 1, 'is_active' => true,
        ]);

        $this->clearCache();

        return $this->ok($request, 'Item added.', [
            'item' => ['id' => $item->id, 'is_active' => true, 'sort_order' => $item->sort_order] + $fields,
        ]);
    }

    public function updateItem(Request $request, string $id)
    {
        $item = SiteContent::findOrFail($id);
        $schema = self::schema();
        $defs = $schema[$item->page]['list'][$item->section]['fields'] ?? [];

        $existing = json_decode($item->value, true) ?: [];
        $fields = $this->collectItemFields($request, $defs, $item->page, $existing);

        $item->update(['value' => json_encode($fields), 'is_active' => $request->boolean('is_active')]);

        $this->clearCache();

        return $this->ok($request, 'Item updated.', [
            'item' => ['id' => $item->id, 'is_active' => $item->is_active, 'sort_order' => $item->sort_order] + $fields,
        ]);
    }

    public function deleteItem(Request $request, string $id)
    {
        SiteContent::findOrFail($id)->delete();
        $this->clearCache();

        return $this->ok($request, 'Item deleted.');
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('ids', []) as $order => $id) {
            SiteContent::where('id', $id)->update(['sort_order' => $order + 1]);
        }
        $this->clearCache();

        return response()->json(['ok' => true]);
    }

    // ---------------------------------------------------------------------

    private function collectItemFields(Request $request, array $defs, string $page, array $existing = []): array
    {
        $fields = [];
        foreach ($defs as $key => [$label, $type]) {
            if ($type === 'image') {
                $fields[$key] = $request->hasFile($key)
                    ? $this->storeImage($request, $key, $page)
                    : ($existing[$key] ?? '');
            } else {
                $fields[$key] = (string) $request->input($key, $existing[$key] ?? '');
            }
        }

        return $fields;
    }

    private function putValue(string $page, string $locale, string $section, string $key, string $type, ?string $value): void
    {
        SiteContent::updateOrCreate(
            ['page' => $page, 'locale' => $locale, 'section' => $section, 'key' => $key],
            ['type' => $type, 'value' => $value]
        );
    }

    private function storeImage(Request $request, string $key, string $page): string
    {
        $file = $request->file($key);
        $ext = $file->getClientOriginalExtension();

        return $file->storeAs('website/' . $page, $key . '-' . Str::random(8) . '.' . $ext, 'public');
    }

    private function clearCache(): void
    {
        // Public helpers read fresh per request; nothing persistent to flush yet.
        // Hook kept for future page-level caching.
    }

    private function ok(Request $request, string $message, array $extra = [])
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message] + $extra);
        }

        return back()->with('success', $message);
    }
}
