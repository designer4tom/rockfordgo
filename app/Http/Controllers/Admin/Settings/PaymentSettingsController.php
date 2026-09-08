<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;

class PaymentSettingsController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
        ];
    }

    // Payment method toggles + wallet/recharge limits moved to Business Settings.
    // This page now only hosts the MultiPay gateway manager (self-contained blade).
    public function index()
    {
        return view('admin.settings.payment');
    }
}
