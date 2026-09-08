<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/pages/{slug}?app_type=customer|driver
     * Resolves the app-specific page, falling back to the 'common' variant.
     */
    public function show(Request $request, string $slug)
    {
        $appType = $request->query('app_type');
        if (! in_array($appType, Page::APP_TYPES, true)) {
            $appType = 'common';
        }

        $page = Page::where('slug', $slug)->where('is_active', true)
            ->where('app_type', $appType)->first()
            // Fall back to the common variant when no app-specific page exists.
            ?? Page::where('slug', $slug)->where('is_active', true)
                ->where('app_type', 'common')->first();

        if (! $page) {
            return $this->error('Page not found.', 404);
        }

        return $this->success([
            'slug' => $page->slug,
            'app_type' => $page->app_type,
            'title' => $page->title,
            'content' => $page->content ?? '',
            'updated_at' => $page->updated_at?->toISOString(),
        ], 'Page fetched.');
    }
}
