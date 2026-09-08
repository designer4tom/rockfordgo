<?php

namespace App\Providers;

use App\Models\Language;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin-managed integration keys (Pusher, Google Maps) override .env at runtime.
        $this->applyDynamicConfig();

        // Opens the ride/parcel chat when a driver is assigned and closes it when
        // the order ends — on every status path, not just OrderStatusService.
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        // MultiPay: fulfil wallet recharge / top-up when a payment is verified.
        \Illuminate\Support\Facades\Event::listen(
            \Abedin\MultiPay\Events\PaymentSucceeded::class,
            [\App\Listeners\CompleteMultiPayPayment::class, 'handleSucceeded'],
        );
        \Illuminate\Support\Facades\Event::listen(
            \Abedin\MultiPay\Events\PaymentFailed::class,
            [\App\Listeners\CompleteMultiPayPayment::class, 'handleFailed'],
        );

        // Feed the public layout (nav/footer language switcher) with real data.
        View::composer('website.public', function ($view) {
            $languages = collect();
            if (Schema::hasTable('languages')) {
                $languages = Language::where('is_active', true)->orderBy('sort_order')->get();
            }
            if ($languages->isEmpty()) {
                $languages = collect([new Language([
                    'name' => 'en', 'title' => 'English', 'language_picture' => 'assets/images/flags/us.png', 'is_default' => true,
                ])]);
            }

            $locale = siteLocale();
            $current = $languages->firstWhere('name', $locale) ?? $languages->first();

            $view->with([
                'publicV2Languages' => $languages,
                'publicV2CurrentLanguage' => $current,
                'publicV2Locale' => $locale,
                'publicV2Dir' => $locale === 'ar' ? 'rtl' : 'ltr',
            ]);
        });
    }

    /**
     * Override .env-based integration config with admin-managed values
     * (system_settings). Pusher + Google Maps are configured from the panel;
     * Stripe still comes from .env for now. Falls back to .env silently when
     * settings are unavailable (e.g. during migrations).
     */
    private function applyDynamicConfig(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        try {
            $settings = app(SystemSettingService::class);

            // Pusher (broadcasting) — drives both the server broadcaster and /config.
            if ($appId = $settings->get('pusher_app_id')) {
                $cluster = $settings->get('pusher_cluster', 'ap2');
                config([
                    'broadcasting.connections.pusher.app_id' => $appId,
                    'broadcasting.connections.pusher.key' => (string) $settings->get('pusher_key'),
                    'broadcasting.connections.pusher.secret' => $this->decryptSetting($settings->get('pusher_secret')),
                    'broadcasting.connections.pusher.options.cluster' => $cluster,
                    'broadcasting.connections.pusher.options.host' => 'api-' . $cluster . '.pusher.com',
                ]);
            }

            // Google Maps — admin-managed key for maps/geocode.
            if ($maps = $settings->get('google_maps_key')) {
                config(['services.google_maps.key' => $maps]);
            }
        } catch (\Throwable $e) {
            // Settings table not ready / DB down — keep .env defaults.
        }
    }

    // Decrypt a stored secret, tolerating legacy plain-text values.
    private function decryptSetting(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
