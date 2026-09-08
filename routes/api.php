<?php

use App\Http\Controllers\Api\Auth\CustomerAuthController;
use App\Http\Controllers\Api\Auth\DriverAuthController;
use App\Http\Controllers\Api\Customer\CustomerProfileController;
use App\Http\Controllers\Api\Customer\ComplaintController;
use App\Http\Controllers\Api\Customer\CouponController;
use App\Http\Controllers\Api\Customer\EmergencyContactController;
use App\Http\Controllers\Api\Customer\FavouriteLocationController;
use App\Http\Controllers\Api\Customer\GeocodeController;
use App\Http\Controllers\Api\Customer\HomeController;
use App\Http\Controllers\Api\Customer\InvoiceController;
use App\Http\Controllers\Api\Customer\OrderHistoryController;
use App\Http\Controllers\Api\Customer\ParcelController;
use App\Http\Controllers\Api\Customer\ReferralController;
use App\Http\Controllers\Api\Customer\RideController;
use App\Http\Controllers\Api\Customer\ScheduledOrderController;
use App\Http\Controllers\Api\Customer\CustomerTopupController;
use App\Http\Controllers\Api\Customer\WalletController;
use App\Http\Controllers\Api\Driver\DriverComplaintController;
use App\Http\Controllers\Api\Driver\DriverEarningsController;
use App\Http\Controllers\Api\Driver\DriverOnlineController;
use App\Http\Controllers\Api\Driver\DriverOrderHistoryController;
use App\Http\Controllers\Api\Driver\DriverParcelController;
use App\Http\Controllers\Api\Driver\DriverPerformanceController;
use App\Http\Controllers\Api\Driver\DriverProfileController;
use App\Http\Controllers\Api\Driver\DriverRechargeController;
use App\Http\Controllers\Api\Driver\DriverVehicleCategoryController;
use App\Http\Controllers\Api\Driver\DriverRideController;
use App\Http\Controllers\Api\Driver\DriverShiftController;
use App\Http\Controllers\Api\Driver\DriverWalletController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Shared\BannerController;
use App\Http\Controllers\Api\Shared\ChatController;
use App\Http\Controllers\Api\Shared\ConfigController;
use App\Http\Controllers\Api\Shared\FaqController;
use App\Http\Controllers\Api\Shared\PageController;
use App\Http\Controllers\Api\Shared\StripeWebhookController;
use App\Http\Controllers\Api\SosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Private broadcast channel authorization for the mobile apps (token auth).
// Registered at both /api/broadcasting/auth and /api/v1/broadcasting/auth so
// apps using the v1 base URL work too.
$broadcastingAuth = function (Request $request) {
    return Broadcast::auth($request);
};
Route::post('/broadcasting/auth', $broadcastingAuth)->middleware('auth:sanctum');

Route::prefix('v1')->group(function () use ($broadcastingAuth) {

    Route::post('/broadcasting/auth', $broadcastingAuth)->middleware('auth:sanctum');

    // ── Home & Ride (public) ────────────────────────────────
    Route::get('services', [HomeController::class, 'services']);
    Route::get('ride/vehicle-categories', [HomeController::class, 'vehicleCategories']);
    Route::post('ride/fare-estimate', [HomeController::class, 'fareEstimate']);
    Route::get('nearby-drivers', [HomeController::class, 'nearbyDrivers']);
    Route::get('trip-share/{token}', [RideController::class, 'publicTracking']);
    Route::get('parcel-track/{orderNumber}', [ParcelController::class, 'publicTracking']);
    Route::get('config', [ConfigController::class, 'index']);
    Route::post('webhook/stripe', [StripeWebhookController::class, 'handle']);
    Route::get('faqs', [FaqController::class, 'index']);
    Route::get('safety-tips', [\App\Http\Controllers\Api\Shared\SafetyTipController::class, 'index']);
    Route::get('banners', [BannerController::class, 'index']);
    Route::get('withdrawal-methods', [\App\Http\Controllers\Api\Shared\WithdrawalMethodController::class, 'index']);
    Route::get('pages/{slug}', [PageController::class, 'show']);
    Route::get('cancellation-reasons', [ConfigController::class, 'cancellationReasons']);

    // ── Customer Auth (public) ──────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('send-otp', [CustomerAuthController::class, 'sendOtp']);
        Route::post('verify-otp', [CustomerAuthController::class, 'verifyOtp']);
        Route::post('complete-profile', [CustomerAuthController::class, 'completeProfile'])->middleware('auth:sanctum');
        Route::post('logout', [CustomerAuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('refresh-token', [CustomerAuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    });

    // ── Customer (protected) ────────────────────────────────
    Route::prefix('user')->middleware(['auth:sanctum', 'auth.user'])->group(function () {
        Route::get('profile', [CustomerProfileController::class, 'show']);
        Route::put('profile', [CustomerProfileController::class, 'update']);
        Route::post('profile', [CustomerProfileController::class, 'update']); // multipart fallback
        Route::post('update-fcm-token', [CustomerProfileController::class, 'updateFcmToken']);
    });

    // ── Customer Ride (protected) ───────────────────────────
    Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
        Route::post('ride/book', [RideController::class, 'book']);
        Route::get('ride/{orderId}/status', [RideController::class, 'status'])->whereNumber('orderId');
        Route::post('ride/{orderId}/cancel', [RideController::class, 'cancel'])->whereNumber('orderId');
        Route::post('ride/{orderId}/tip', [RideController::class, 'addTip'])->whereNumber('orderId');
        Route::post('ride/{orderId}/rate', [RideController::class, 'rate'])->whereNumber('orderId');
        Route::post('ride/{orderId}/generate-share-link', [RideController::class, 'generateShareLink'])->whereNumber('orderId');

        // Parcel booking
        Route::post('parcel/estimate', [ParcelController::class, 'estimate']);
        Route::post('parcel/book', [ParcelController::class, 'book']);
        Route::get('parcel/{orderId}/status', [ParcelController::class, 'status'])->whereNumber('orderId');
        Route::post('parcel/{orderId}/cancel', [ParcelController::class, 'cancel'])->whereNumber('orderId');
        Route::post('parcel/{orderId}/pay-after-delivery', [ParcelController::class, 'payAfterDelivery'])->whereNumber('orderId');
        Route::post('parcel/{orderId}/rate', [ParcelController::class, 'rate'])->whereNumber('orderId');

        // Ride/parcel chat with the assigned driver. Opened automatically when a
        // driver accepts; closed when the order completes or is cancelled.
        Route::prefix('chat')->group(function () {
            Route::get('conversations', [ChatController::class, 'index']);
            Route::get('unread-count', [ChatController::class, 'unreadCount']);
            Route::get('order/{orderId}', [ChatController::class, 'showForOrder'])->whereNumber('orderId');
            Route::get('{conversation}/messages', [ChatController::class, 'messages'])->whereNumber('conversation');
            Route::post('{conversation}/send', [ChatController::class, 'send'])->whereNumber('conversation');
            Route::post('{conversation}/read', [ChatController::class, 'markRead'])->whereNumber('conversation');
            Route::post('heartbeat', [ChatController::class, 'heartbeat']);
            Route::post('offline', [ChatController::class, 'offline']);
        });

        // Wallet & payments
        Route::get('user/wallet', [WalletController::class, 'balance']);
        Route::get('user/wallet/transactions', [WalletController::class, 'transactions']);
        Route::get('user/wallet/dues', [WalletController::class, 'dues']);
        Route::post('user/wallet/topup/initiate', [WalletController::class, 'initiateTopup']);
        Route::post('user/wallet/topup/confirm', [WalletController::class, 'confirmTopup']);

        // Add Money — gateway Checkout-redirect flow (same as driver recharge).
        Route::get('user/wallet/payment-methods', [CustomerTopupController::class, 'paymentMethods']);
        Route::post('user/wallet/add-money/initiate', [CustomerTopupController::class, 'initiate']);
        Route::post('user/wallet/add-money/confirm', [CustomerTopupController::class, 'confirm']);
        Route::get('user/wallet/add-money/history', [CustomerTopupController::class, 'history']);
        Route::post('user/wallet/withdrawal/request', [WalletController::class, 'requestWithdrawal']);
        Route::get('user/wallet/withdrawal/history', [WalletController::class, 'withdrawalHistory']);
        Route::get('orders/{orderId}/invoice', [InvoiceController::class, 'show'])->whereNumber('orderId');
        Route::get('orders/{orderId}/invoice/pdf', [InvoiceController::class, 'pdf'])->whereNumber('orderId');

        // Active order (resume tracking after app kill/restart)
        Route::get('user/active-order', [HomeController::class, 'activeOrder']);

        // History
        Route::get('user/orders', [OrderHistoryController::class, 'index']);
        Route::get('user/orders/{id}', [OrderHistoryController::class, 'show'])->whereNumber('id');
        // Post-trip online payment (MultiPay hosted checkout + verified status poll).
        Route::post('user/orders/{orderId}/pay-online', [\App\Http\Controllers\Api\Customer\OrderPaymentController::class, 'initiate'])->whereNumber('orderId');
        Route::get('user/orders/{orderId}/payment-status', [\App\Http\Controllers\Api\Customer\OrderPaymentController::class, 'status'])->whereNumber('orderId');

        // Favourite locations
        Route::get('user/favourite-locations', [FavouriteLocationController::class, 'index']);
        Route::post('user/favourite-locations', [FavouriteLocationController::class, 'store']);
        Route::put('user/favourite-locations/{id}', [FavouriteLocationController::class, 'update'])->whereNumber('id');
        Route::delete('user/favourite-locations/{id}', [FavouriteLocationController::class, 'destroy'])->whereNumber('id');

        // Notifications
        Route::get('user/notifications', [NotificationController::class, 'userIndex']);
        Route::post('user/notifications/mark-read', [NotificationController::class, 'markRead']);

        // SOS (max 3 per hour), referral, complaints
        Route::post('user/sos', [SosController::class, 'userSos'])->middleware('throttle:3,60');
        Route::get('user/referral', [ReferralController::class, 'index']);
        Route::get('user/complaints', [ComplaintController::class, 'index']);
        Route::post('user/complaints', [ComplaintController::class, 'store']);

        // ── Phase 6 supplement (customer) ──
        Route::post('coupon/validate', [CouponController::class, 'validate']);
        Route::get('coupons', [CouponController::class, 'index']);
        Route::get('user/scheduled-orders', [ScheduledOrderController::class, 'index']);
        Route::post('user/scheduled-orders/{orderId}/cancel', [ScheduledOrderController::class, 'cancel'])->whereNumber('orderId');
        Route::get('user/emergency-contact', [EmergencyContactController::class, 'show']);
        Route::post('user/emergency-contact', [EmergencyContactController::class, 'store']);
        Route::delete('user/account', [\App\Http\Controllers\Api\Customer\CustomerProfileController::class, 'deleteAccount']);
        Route::get('geocode/search', [GeocodeController::class, 'search']);
        Route::get('geocode/place', [GeocodeController::class, 'place']);
        Route::get('geocode/reverse', [GeocodeController::class, 'reverse']);
    });

    // ── Driver Auth (public + onboarding) ───────────────────
    Route::prefix('driver/auth')->group(function () {
        Route::post('send-otp', [DriverAuthController::class, 'sendOtp']);
        Route::post('verify-otp', [DriverAuthController::class, 'verifyOtp']);
        Route::post('register', [DriverAuthController::class, 'register'])->middleware('auth:sanctum');
        Route::get('status', [DriverAuthController::class, 'status'])->middleware('auth:sanctum');
        Route::post('logout', [DriverAuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    // Vehicle categories for driver registration / vehicle setup (public —
    // a new driver is still pending and won't pass the approved-driver guard).
    Route::get('driver/vehicle-categories', [DriverVehicleCategoryController::class, 'index']);

    // ── Driver (protected — approved only) ──────────────────
    Route::prefix('driver')->middleware(['auth:sanctum', 'auth.driver'])->group(function () {
        Route::get('profile', [DriverProfileController::class, 'show']);
        Route::put('profile', [DriverProfileController::class, 'update']);
        Route::post('profile', [DriverProfileController::class, 'update']); // multipart fallback
        Route::post('update-fcm-token', [DriverProfileController::class, 'updateFcmToken']);
        Route::post('documents/update', [DriverProfileController::class, 'updateDocument']);

        // Chat with the customer — same endpoints as the customer app, scoped to
        // the driver by the Sanctum token.
        Route::prefix('chat')->group(function () {
            Route::get('conversations', [ChatController::class, 'index']);
            Route::get('unread-count', [ChatController::class, 'unreadCount']);
            Route::get('order/{orderId}', [ChatController::class, 'showForOrder'])->whereNumber('orderId');
            Route::get('{conversation}/messages', [ChatController::class, 'messages'])->whereNumber('conversation');
            Route::post('{conversation}/send', [ChatController::class, 'send'])->whereNumber('conversation');
            Route::post('{conversation}/read', [ChatController::class, 'markRead'])->whereNumber('conversation');
            Route::post('heartbeat', [ChatController::class, 'heartbeat']);
            Route::post('offline', [ChatController::class, 'offline']);
        });

        // Online status & location
        Route::post('toggle-online', [DriverOnlineController::class, 'toggleOnline']);
        Route::post('update-location', [DriverOnlineController::class, 'updateLocation'])->middleware('throttle:60,1');
        Route::get('active-order', [DriverOnlineController::class, 'activeOrder']);

        // Ride handling
        Route::post('ride/respond', [DriverRideController::class, 'respond']);
        Route::post('ride/update-status', [DriverRideController::class, 'updateStatus']);
        Route::post('ride/{orderId}/cancel', [DriverRideController::class, 'cancel'])->whereNumber('orderId');
        Route::post('ride/collect-proof', [DriverRideController::class, 'collectProof']);
        Route::post('ride/{orderId}/rate', [DriverRideController::class, 'rate'])->whereNumber('orderId');

        // Parcel handling
        Route::post('parcel/update-status', [DriverParcelController::class, 'updateStatus']);
        Route::post('parcel/collect-cod', [DriverParcelController::class, 'collectCod']);
        Route::post('parcel/complete', [DriverParcelController::class, 'complete']);

        // Wallet & withdrawal
        Route::get('wallet', [DriverWalletController::class, 'balance']);
        Route::get('wallet/transactions', [DriverWalletController::class, 'transactions']);
        Route::post('wallet/withdrawal/request', [DriverWalletController::class, 'requestWithdrawal']);
        Route::get('wallet/withdrawal/history', [DriverWalletController::class, 'withdrawalHistory']);

        // Recharge (top-up). On success, due is cleared first then balance is added.
        Route::get('recharge/payment-methods', [DriverRechargeController::class, 'paymentMethods']);
        Route::post('recharge/initiate', [DriverRechargeController::class, 'initiate']);
        Route::post('recharge/confirm', [DriverRechargeController::class, 'confirm']);
        Route::get('recharge/history', [DriverRechargeController::class, 'history']);

        // History
        Route::get('orders', [DriverOrderHistoryController::class, 'index']);
        Route::get('orders/{id}', [DriverOrderHistoryController::class, 'show'])->whereNumber('id');

        // Notifications
        Route::get('notifications', [NotificationController::class, 'driverIndex']);
        Route::post('notifications/mark-read', [NotificationController::class, 'driverMarkRead']);

        // SOS + earnings
        Route::post('sos', [SosController::class, 'driverSos'])->middleware('throttle:3,60');
        Route::get('earnings', [DriverEarningsController::class, 'index']);
        Route::get('earnings/summary', [DriverEarningsController::class, 'summary']);

        // ── Phase 6 supplement (driver) ──
        Route::get('earnings/chart', [DriverEarningsController::class, 'chart']);
        Route::get('performance', [DriverPerformanceController::class, 'index']);
        Route::get('shifts', [DriverShiftController::class, 'index']);
        Route::get('complaints', [DriverComplaintController::class, 'index']);
        Route::post('complaints', [DriverComplaintController::class, 'store']);
        Route::get('emergency-contact', [EmergencyContactController::class, 'driverShow']);
        Route::post('emergency-contact', [EmergencyContactController::class, 'driverStore']);
        Route::delete('account', [DriverProfileController::class, 'deleteAccount']);
    });

});
