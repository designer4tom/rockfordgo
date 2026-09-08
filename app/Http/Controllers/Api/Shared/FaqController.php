<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\LandingPageContent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $category = $request->query('category');

        $faqs = LandingPageContent::where('section', 'faq')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => json_decode($row->value, true) ?: [])
            ->when($category, fn ($c) => $c->filter(fn ($f) => ($f['category'] ?? null) === $category))
            ->map(fn ($f) => [
                'question' => $f['question'] ?? null,
                'answer' => $f['answer'] ?? null,
                'category' => $f['category'] ?? 'general',
            ])
            ->values();

        return $this->success($faqs, 'FAQs fetched.');
    }
}
