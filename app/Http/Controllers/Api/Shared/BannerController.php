<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $banners = Banner::active()->get()->map(fn (Banner $banner) => [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'image' => $banner->image ? asset(Storage::url($banner->image)) : null,
            'button_text' => $banner->button_text,
            'action_type' => $banner->action_type,
            'action_value' => $banner->action_value,
        ])->values();

        return $this->success($banners, 'Banners fetched.');
    }
}
