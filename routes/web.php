<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerWalletController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\DriverDocumentController;
use App\Http\Controllers\Admin\DriverWalletController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderInterventionController;
use App\Http\Controllers\Admin\ParcelPricingController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\Reports\CustomerReportController;
use App\Http\Controllers\Admin\Reports\DriverReportController;
use App\Http\Controllers\Admin\Reports\OrderReportController;
use App\Http\Controllers\Admin\Reports\RevenueReportController;
use App\Http\Controllers\Admin\Payments\CodReconciliationController;
use App\Http\Controllers\Admin\Payments\CustomerDueController;
use App\Http\Controllers\Admin\Payments\DueController;
use App\Http\Controllers\Admin\Payments\RefundController;
use App\Http\Controllers\Admin\Payments\RevenueController;
use App\Http\Controllers\Admin\Payments\TransactionController;
use App\Http\Controllers\Admin\Payments\WithdrawalController;
use App\Http\Controllers\Admin\Payments\WithdrawalMethodController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ScheduledOrderController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\Settings\AdvancedSettingsController;
use App\Http\Controllers\Admin\Settings\GeneralSettingsController;
use App\Http\Controllers\Admin\Settings\LanguageController;
use App\Http\Controllers\Admin\Settings\MapSettingsController;
use App\Http\Controllers\Admin\Settings\NotificationSettingsController;
use App\Http\Controllers\Admin\Settings\PaymentSettingsController;
use App\Http\Controllers\Admin\Settings\PricingSettingsController;
use App\Http\Controllers\Admin\SosController;
use App\Http\Controllers\Admin\SubAdminController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\Admin\SurgePricingController;
use App\Http\Controllers\Admin\VehicleCategoryController;
use App\Http\Controllers\Admin\WebsiteContentController;
use App\Http\Controllers\Admin\ZoneController;
use Illuminate\Support\Facades\Route;

// Public marketing site (no auth) — content/images managed via the Website CMS.
Route::get('/', [WebsiteController::class, 'show'])->defaults('page', 'home')->name('landing');
// The landing page lives at the domain root only. Old /home links 301-redirect
// there (no duplicate page — better for SEO). route('home') is intentionally gone.
Route::redirect('/home', '/', 301);
Route::get('/features', [WebsiteController::class, 'show'])->defaults('page', 'features')->name('features');
Route::get('/safety', [WebsiteController::class, 'show'])->defaults('page', 'safety')->name('safety');
Route::get('/help', [WebsiteController::class, 'show'])->defaults('page', 'help')->name('help');
Route::get('/about', [WebsiteController::class, 'show'])->defaults('page', 'about')->name('about');
// Admin-managed pages (Privacy, Terms, …) rendered publicly so they can be
// linked from the website footer. Slug comes from Admin → Pages.
Route::get('/page/{slug}', [WebsiteController::class, 'page'])->name('page.show');
Route::get('/public/change-language/{name}', [WebsiteController::class, 'changeLanguage'])->name('public.change.language');

/*
|--------------------------------------------------------------------------
| Web Installer (guided setup for fresh deployments)
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->controller(\App\Http\Controllers\Install\InstallController::class)->group(function () {
    Route::get('/', 'requirements')->name('requirements');
    Route::get('database', 'database')->name('database');
    Route::post('database', 'saveDatabase')->name('database.save');
    Route::get('migrate', 'migrate')->name('migrate');
    Route::post('migrate', 'runMigrate')->name('migrate.run');
    Route::get('admin', 'admin')->name('admin');
    Route::post('admin', 'saveAdmin')->name('admin.save');
    Route::get('finished', 'finished')->name('finished');
});

/*
|--------------------------------------------------------------------------
| Payment Gateway Callbacks (public — gateway redirects the browser here)
|--------------------------------------------------------------------------
*/
// Legacy PaymentService callbacks (driver recharge / customer top-up — still in use).
Route::get('/payment/success', [\App\Http\Controllers\PaymentCallbackController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [\App\Http\Controllers\PaymentCallbackController::class, 'cancel'])->name('payment.cancel');

// MultiPay (joynala/multi-pay) return endpoints — one set covers all gateways.
// GET+POST because some gateways return via POST. Names are the package contract.
Route::match(['get', 'post'], '/payment/{session}/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('multipay.success');
Route::match(['get', 'post'], '/payment/{session}/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('multipay.cancel');
Route::match(['get', 'post'], '/payment/{session}/failure', [\App\Http\Controllers\PaymentController::class, 'failure'])->name('multipay.failure');
Route::match(['get', 'post'], '/payment/{session}/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('multipay.callback');

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // Language & theme preferences — cookie-based, work before and after login.
    Route::get('set-locale/{locale}', function (string $locale, \App\Services\TranslationManager $tm) {
        if (! $tm->exists($locale)) {
            $locale = $tm->defaultLocale();
        }

        return back()->withCookie(cookie('admin_locale', $locale, 60 * 24 * 365));
    })->name('set-locale');

    Route::get('set-theme/{theme}', function (string $theme) {
        // 'system' follows the OS preference; resolved client-side before paint.
        $theme = in_array($theme, ['light', 'dark', 'system'], true) ? $theme : 'light';

        return back()->withCookie(cookie('admin_theme', $theme, 60 * 24 * 365));
    })->name('set-theme');

    // Guest (login + 2FA challenge) routes.
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
        Route::get('two-factor', [AuthController::class, 'showTwoFactor'])->name('two-factor');
        Route::post('two-factor', [AuthController::class, 'verifyTwoFactor'])->name('two-factor.post');
    });

    // Authenticated admin routes.
    Route::middleware('admin.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('login-history', [AuthController::class, 'loginHistory'])->name('login-history');

        // Sidebar drag-and-drop order (per admin, display preference only).
        Route::post('nav-order', [\App\Http\Controllers\Admin\NavOrderController::class, 'store'])->name('nav-order.store');
        Route::post('nav-order/reset', [\App\Http\Controllers\Admin\NavOrderController::class, 'reset'])->name('nav-order.reset');

        // Per-admin show/hide of table columns.
        Route::post('table-columns', [\App\Http\Controllers\Admin\NavOrderController::class, 'columns'])->name('table-columns');

        // Top-bar omnisearch (records + page jump list).
        Route::get('search', \App\Http\Controllers\Admin\GlobalSearchController::class)
            ->middleware('throttle:60,1')
            ->name('search');

        // Two-factor management (enable / disable).
        Route::get('two-factor/setup', [AuthController::class, 'showTwoFactorSetup'])->name('two-factor.setup');
        Route::post('two-factor/setup', [AuthController::class, 'enableTwoFactor'])->name('two-factor.enable');
        Route::post('two-factor/disable', [AuthController::class, 'disableTwoFactor'])->name('two-factor.disable');

        // Dashboard.
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::view('setup-guide', 'admin.setup-guide')->name('setup-guide');
        Route::get('dashboard/stats', [DashboardController::class, 'statsJson'])->name('dashboard.stats');
        Route::get('dashboard/online-drivers', [DashboardController::class, 'onlineDriversJson'])->name('dashboard.online-drivers');

        // Sub-admin management. Viewing requires settings:read; mutations are
        // restricted to super admins.
        Route::middleware('permission:settings,read')->group(function () {
            Route::get('sub-admins', [SubAdminController::class, 'index'])->name('sub-admins.index');
        });

        Route::middleware('admin.super')->group(function () {
            Route::get('sub-admins/create', [SubAdminController::class, 'create'])->name('sub-admins.create');
            Route::post('sub-admins', [SubAdminController::class, 'store'])->name('sub-admins.store');
            Route::get('sub-admins/{id}/edit', [SubAdminController::class, 'edit'])->name('sub-admins.edit');
            Route::put('sub-admins/{id}', [SubAdminController::class, 'update'])->name('sub-admins.update');
            Route::delete('sub-admins/{id}', [SubAdminController::class, 'destroy'])->name('sub-admins.destroy');
            Route::post('sub-admins/{id}/toggle-status', [SubAdminController::class, 'toggleStatus'])->name('sub-admins.toggle-status');
        });

        // ---- Phase 3: Services, Pricing, Zones, Surge ----
        // (per-action permission checks live in each controller's middleware())

        // Services
        Route::post('services/{id}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
        Route::resource('services', ServiceController::class)->except('show');

        // Banners (home-screen carousel CMS)
        Route::post('banners/{id}/toggle-status', [BannerController::class, 'toggleStatus'])->name('banners.toggle-status');
        Route::resource('banners', BannerController::class)->except('show')->parameters(['banners' => 'id']);

        // Safety Tips (Help & Safety)
        Route::post('safety-tips/{id}/toggle-status', [\App\Http\Controllers\Admin\SafetyTipController::class, 'toggleStatus'])->name('safety-tips.toggle-status');
        Route::resource('safety-tips', \App\Http\Controllers\Admin\SafetyTipController::class)->except('show')->parameters(['safety-tips' => 'id']);

        // Pages (Privacy / Terms / About — per app_type)
        Route::resource('pages', \App\Http\Controllers\Admin\PageController::class)->except('show')->parameters(['pages' => 'id']);

        // Vehicle Categories
        Route::post('vehicle-categories/{id}/toggle-status', [VehicleCategoryController::class, 'toggleStatus'])->name('vehicle-categories.toggle-status');
        Route::resource('vehicle-categories', VehicleCategoryController::class)->except('show');

        // Parcel Pricing
        // Commission moved to Business Settings (admin.settings.business).
        Route::post('parcel-pricing/{id}/toggle-status', [ParcelPricingController::class, 'toggleStatus'])->name('parcel-pricing.toggle-status');
        Route::resource('parcel-pricing', ParcelPricingController::class)->except('show')->parameters(['parcel-pricing' => 'id']);

        // Zones
        Route::post('zones/{id}/toggle-status', [ZoneController::class, 'toggleStatus'])->name('zones.toggle-status');
        Route::resource('zones', ZoneController::class);

        // Surge Pricing
        Route::post('surge-pricing/manual-activate', [SurgePricingController::class, 'manualActivate'])->name('surge-pricing.manual-activate');
        Route::post('surge-pricing/toggle-master', [SurgePricingController::class, 'toggleMaster'])->name('surge-pricing.toggle-master');
        Route::post('surge-pricing/{id}/toggle-status', [SurgePricingController::class, 'toggleStatus'])->name('surge-pricing.toggle-status');
        Route::resource('surge-pricing', SurgePricingController::class)->except('show')->parameters(['surge-pricing' => 'id']);

        // Pricing Settings
        Route::get('settings/pricing', [PricingSettingsController::class, 'index'])->name('settings.pricing');
        Route::post('settings/pricing', [PricingSettingsController::class, 'update'])->name('settings.pricing.update');

        // Payment Settings (Stripe)
        Route::get('settings/payment', [PaymentSettingsController::class, 'index'])->name('settings.payment');

        // Language / Translation Manager
        Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::post('languages/{locale}/default', [LanguageController::class, 'setDefault'])->name('languages.default');
        Route::get('languages/{locale}/edit', [LanguageController::class, 'edit'])->name('languages.edit');
        Route::put('languages/{locale}', [LanguageController::class, 'update'])->name('languages.update');
        Route::delete('languages/{locale}', [LanguageController::class, 'destroy'])->name('languages.destroy');

        // Notification Settings (FCM / Pusher / SMS)
        Route::get('settings/notifications', [NotificationSettingsController::class, 'index'])->name('settings.notifications');
        Route::post('settings/notifications', [NotificationSettingsController::class, 'update'])->name('settings.notifications.update');
        Route::post('settings/notifications/test-fcm', [NotificationSettingsController::class, 'testFcm'])->name('settings.notifications.test-fcm');
        Route::post('settings/notifications/test-pusher', [NotificationSettingsController::class, 'testPusher'])->name('settings.notifications.test-pusher');
        Route::post('settings/notifications/test-whatsapp', [NotificationSettingsController::class, 'testWhatsapp'])->name('settings.notifications.test-whatsapp');
        Route::post('settings/notifications/test-sms', [NotificationSettingsController::class, 'testSms'])->name('settings.notifications.test-sms');

        // ---- Phase 4: Driver Management ----
        Route::prefix('drivers')->name('drivers.')->group(function () {
            Route::get('/', [DriverController::class, 'index'])->name('index');
            Route::get('/pending', [DriverController::class, 'pending'])->name('pending');
            Route::get('/expiring-documents', [DriverController::class, 'expiringDocuments'])->name('expiring-documents');
            Route::post('/expiring-documents/notify-all', [DriverController::class, 'notifyExpiring'])->name('expiring-documents.notify-all');
            Route::get('/export/csv', [DriverController::class, 'export'])->name('export');
            Route::get('/import/template', [DriverController::class, 'importTemplate'])->name('import.template');
            Route::post('/import', [DriverController::class, 'import'])->name('import');
            Route::post('/bulk-action', [DriverController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/create', [DriverController::class, 'create'])->name('create');
            Route::post('/', [DriverController::class, 'store'])->name('store');

            Route::get('/{id}', [DriverController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/edit', [DriverController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [DriverController::class, 'update'])->whereNumber('id')->name('update');
            Route::get('/{id}/review', [DriverController::class, 'review'])->whereNumber('id')->name('review');
            Route::post('/{id}/approve', [DriverController::class, 'approve'])->whereNumber('id')->name('approve');
            Route::post('/{id}/reject', [DriverController::class, 'reject'])->whereNumber('id')->name('reject');
            Route::post('/{id}/change-status', [DriverController::class, 'changeStatus'])->whereNumber('id')->name('change-status');
            Route::post('/{id}/notify', [DriverController::class, 'notify'])->whereNumber('id')->name('notify');

            // Documents
            Route::post('/{id}/documents/{docId}/approve', [DriverDocumentController::class, 'approve'])->name('documents.approve');
            Route::post('/{id}/documents/{docId}/reject', [DriverDocumentController::class, 'reject'])->name('documents.reject');

            // Wallet / Due
            Route::post('/{id}/clear-due', [DriverWalletController::class, 'clearDue'])->whereNumber('id')->name('clear-due');
            Route::post('/{id}/wallet-adjust', [DriverWalletController::class, 'adjust'])->whereNumber('id')->name('wallet-adjust');
            Route::post('/{id}/recharge', [DriverWalletController::class, 'recharge'])->whereNumber('id')->name('recharge');
        });

        // ---- Phase 5: Customer Management ----
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/export/csv', [CustomerController::class, 'export'])->name('export');
            Route::get('/import/template', [CustomerController::class, 'importTemplate'])->name('import.template');
            Route::post('/import', [CustomerController::class, 'import'])->name('import');
            Route::get('/create', [CustomerController::class, 'create'])->name('create');
            Route::post('/', [CustomerController::class, 'store'])->name('store');

            Route::get('/{id}', [CustomerController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/toggle-block', [CustomerController::class, 'toggleBlock'])->whereNumber('id')->name('toggle-block');
            Route::post('/{id}/wallet-adjust', [CustomerWalletController::class, 'adjust'])->whereNumber('id')->name('wallet-adjust');
            Route::post('/{id}/refund', [CustomerWalletController::class, 'refund'])->whereNumber('id')->name('refund');
            Route::post('/{id}/clear-due', [CustomerWalletController::class, 'clearDue'])->whereNumber('id')->name('clear-due');
            Route::post('/{id}/recharge', [CustomerWalletController::class, 'recharge'])->whereNumber('id')->name('recharge');
        });

        // Referral overview
        Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');

        // ---- Phase 6: Order Management ----
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/scheduled', [ScheduledOrderController::class, 'index'])->name('scheduled');
            Route::get('/export', [OrderController::class, 'export'])->name('export');
            Route::get('/{id}', [OrderController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/cancel', [OrderInterventionController::class, 'cancel'])->whereNumber('id')->name('cancel');
            Route::post('/{id}/reassign', [OrderInterventionController::class, 'reassign'])->whereNumber('id')->name('reassign');
            Route::post('/{id}/force-complete', [OrderInterventionController::class, 'forceComplete'])->whereNumber('id')->name('force-complete');
        });

        // Coupons
        Route::post('coupons/{id}/toggle-status', [CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');
        Route::get('coupons/{id}/usages', [CouponController::class, 'usages'])->name('coupons.usages');
        Route::resource('coupons', CouponController::class)->except('show')->parameters(['coupons' => 'id']);

        // ---- Phase 7: Payments, Wallet & Commission ----
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('transactions', [TransactionController::class, 'index'])->name('transactions');
            Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

            Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals');
            Route::post('withdrawals/bulk-approve', [WithdrawalController::class, 'bulkApprove'])->name('withdrawals.bulk-approve');
            Route::post('withdrawals/{id}/approve', [WithdrawalController::class, 'approve'])->whereNumber('id')->name('withdrawals.approve');
            Route::post('withdrawals/{id}/reject', [WithdrawalController::class, 'reject'])->whereNumber('id')->name('withdrawals.reject');

            // Withdrawal methods (bkash/nagad/bank…) — DB-managed, listed via API.
            Route::get('withdrawal-methods', [WithdrawalMethodController::class, 'index'])->name('withdrawal-methods.index');
            Route::post('withdrawal-methods', [WithdrawalMethodController::class, 'store'])->name('withdrawal-methods.store');
            Route::post('withdrawal-methods/{id}/toggle-status', [WithdrawalMethodController::class, 'toggleStatus'])->whereNumber('id')->name('withdrawal-methods.toggle-status');
            Route::put('withdrawal-methods/{id}', [WithdrawalMethodController::class, 'update'])->whereNumber('id')->name('withdrawal-methods.update');
            Route::delete('withdrawal-methods/{id}', [WithdrawalMethodController::class, 'destroy'])->whereNumber('id')->name('withdrawal-methods.destroy');

            Route::get('refunds', [RefundController::class, 'index'])->name('refunds');
            Route::post('refunds', [RefundController::class, 'store'])->name('refunds.store');

            Route::get('cod-reconciliation', [CodReconciliationController::class, 'index'])->name('cod-reconciliation');
            Route::get('cod-reconciliation/export', [CodReconciliationController::class, 'export'])->name('cod-reconciliation.export');

            Route::get('driver-dues', [DueController::class, 'index'])->name('driver-dues');
            Route::get('customer-dues', [CustomerDueController::class, 'index'])->name('customer-dues');

            Route::get('revenue', [RevenueController::class, 'index'])->name('revenue');
        });

        // ---- Phase 8: Disputes, SOS, Notifications ----

        // Notifications must precede the dispute/sos {id} routes are separate prefixes, so order is fine.
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('broadcast', [NotificationController::class, 'broadcastForm'])->name('broadcast');
            Route::post('broadcast', [NotificationController::class, 'sendBroadcast'])->name('broadcast.send');
            Route::get('history', [NotificationController::class, 'history'])->name('history');
            Route::get('/', [NotificationController::class, 'adminNotifications'])->name('list');
            Route::post('mark-read', [NotificationController::class, 'markRead'])->name('mark-read');
        });

        Route::prefix('disputes')->name('disputes.')->group(function () {
            Route::get('/', [DisputeController::class, 'index'])->name('index');
            Route::get('/{id}', [DisputeController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/update', [DisputeController::class, 'update'])->whereNumber('id')->name('update');
        });

        Route::prefix('sos')->name('sos.')->group(function () {
            Route::get('/', [SosController::class, 'index'])->name('index');
            Route::get('/{id}', [SosController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/acknowledge', [SosController::class, 'acknowledge'])->whereNumber('id')->name('acknowledge');
            Route::post('/{id}/resolve', [SosController::class, 'resolve'])->whereNumber('id')->name('resolve');
        });

        // ---- Phase 9: Reports, Settings, Landing CMS, Profile ----
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('revenue', [RevenueReportController::class, 'index'])->name('revenue');
            Route::get('revenue/export', [RevenueReportController::class, 'export'])->name('revenue.export');
            Route::get('revenue/pdf', [RevenueReportController::class, 'pdf'])->name('revenue.pdf');
            Route::get('drivers', [DriverReportController::class, 'index'])->name('drivers');
            Route::get('drivers/export', [DriverReportController::class, 'export'])->name('drivers.export');
            Route::get('orders', [OrderReportController::class, 'index'])->name('orders');
            Route::get('customers', [CustomerReportController::class, 'index'])->name('customers');
        });

        // General / Map / Advanced settings
        Route::get('settings/business', [\App\Http\Controllers\Admin\Settings\BusinessSettingsController::class, 'index'])->name('settings.business');
        Route::post('settings/business', [\App\Http\Controllers\Admin\Settings\BusinessSettingsController::class, 'update'])->name('settings.business.update');

        Route::get('settings/general', [GeneralSettingsController::class, 'index'])->name('settings.general');
        Route::post('settings/general', [GeneralSettingsController::class, 'update'])->name('settings.general.update');
        Route::get('settings/map', [MapSettingsController::class, 'index'])->name('settings.map');
        Route::post('settings/map', [MapSettingsController::class, 'update'])->name('settings.map.update');
        Route::post('settings/map/test-key', [MapSettingsController::class, 'testKey'])->name('settings.map.test-key');
        Route::get('settings/advanced', [AdvancedSettingsController::class, 'index'])->name('settings.advanced');
        Route::post('settings/advanced/clear-cache', [AdvancedSettingsController::class, 'clearCache'])->name('settings.advanced.clear-cache');
        Route::post('settings/advanced/retry-jobs', [AdvancedSettingsController::class, 'retryJobs'])->name('settings.advanced.retry-jobs');
        Route::post('settings/advanced/clear-failed-jobs', [AdvancedSettingsController::class, 'clearFailedJobs'])->name('settings.advanced.clear-failed-jobs');
        Route::post('settings/advanced/clear-log', [AdvancedSettingsController::class, 'clearLog'])->name('settings.advanced.clear-log');
        Route::post('settings/advanced/clear-all-orders', [AdvancedSettingsController::class, 'clearAllOrders'])->name('settings.advanced.clear-all-orders');
        Route::post('settings/advanced/reset-statistics', [AdvancedSettingsController::class, 'resetStatistics'])->name('settings.advanced.reset-statistics');

        // Landing Page CMS
        Route::get('landing-page', [LandingPageController::class, 'index'])->name('landing-page.index');
        Route::post('landing-page/items/store', [LandingPageController::class, 'storeItem'])->name('landing-page.items.store');
        Route::post('landing-page/items/reorder', [LandingPageController::class, 'reorder'])->name('landing-page.items.reorder');
        Route::put('landing-page/items/{id}', [LandingPageController::class, 'updateItem'])->whereNumber('id')->name('landing-page.items.update');
        Route::delete('landing-page/items/{id}', [LandingPageController::class, 'deleteItem'])->whereNumber('id')->name('landing-page.items.delete');
        Route::post('landing-page/{section}', [LandingPageController::class, 'update'])->name('landing-page.update');

        // Website CMS (multilingual public pages: nav/footer + home/features/safety/help/about)
        Route::get('website', [WebsiteContentController::class, 'index'])->name('website.index');
        Route::post('website/items/store', [WebsiteContentController::class, 'storeItem'])->name('website.items.store');
        Route::post('website/items/reorder', [WebsiteContentController::class, 'reorder'])->name('website.items.reorder');
        Route::post('website/items/{id}', [WebsiteContentController::class, 'updateItem'])->whereNumber('id')->name('website.items.update');
        Route::delete('website/items/{id}', [WebsiteContentController::class, 'deleteItem'])->whereNumber('id')->name('website.items.delete');
        Route::post('website/{page}/{section}', [WebsiteContentController::class, 'update'])->name('website.update');

        // Profile
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    });
});
