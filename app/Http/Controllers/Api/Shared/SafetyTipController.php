<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\SafetyTip;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SafetyTipController extends Controller
{
    use ApiResponse;

    // GET /api/v1/safety-tips — active tips, ordered. Cached 5 min.
    public function index()
    {
        $tips = Cache::remember('api_safety_tips', 300, function () {
            return SafetyTip::active()
                ->orderBy('sort_order')
                ->get(['id', 'title', 'description', 'icon'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'icon' => $t->icon ? asset(Storage::url($t->icon)) : null,
                ])
                ->all();
        });

        return $this->success($tips, 'Safety tips fetched.');
    }
}
