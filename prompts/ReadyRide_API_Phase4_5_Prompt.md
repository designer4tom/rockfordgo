# ReadyRide — Laravel API
# API Phase 4: Payment + Wallet + Stripe

---

## Context
API Phase 1-3 শেষ। এই phase-এ Payment, Wallet, Stripe integration বানাবো।

---

## এই phase-এ যা করবে

1. Wallet APIs (Balance, Transactions, Top-up)
2. Stripe Payment APIs
3. Invoice API
4. Driver Withdrawal

---

## ১. Wallet APIs

### GET /api/v1/user/wallet
```
Middleware: auth:sanctum + auth.user

Response (200):
{
    "success": true,
    "data": {
        "balance": "150.00",
        "currency": "BDT",
        "currency_symbol": "৳"
    }
}
```

### GET /api/v1/user/wallet/transactions
```
Query Params: page, per_page (default 20), type (credit/debit), category

Response (200):
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type": "credit",
            "category": "refund",
            "category_label": "Refund",
            "amount": "50.00",
            "balance_after": "150.00",
            "note": "Order #RR-2024-00120 refund",
            "order_number": "RR-2024-00120",
            "created_at": "2024-01-15T10:30:00Z"
        }
    ],
    "meta": { ...pagination... }
}
```

### POST /api/v1/user/wallet/topup/initiate
```
Description: Wallet top-up শুরু করা (Stripe Payment Intent তৈরি)

Request Body:
{
    "amount": 200.00  // min/max admin-defined
}

Response (200):
{
    "success": true,
    "data": {
        "client_secret": "pi_xxx_secret_xxx",  // Stripe PaymentIntent client_secret
        "amount": "200.00",
        "currency": "bdt"
    }
}

Logic:
- Stripe PaymentIntent create করো
- pending_topup cache-এ store করো (user_id → amount)
```

### POST /api/v1/user/wallet/topup/confirm
```
Description: Stripe payment confirm হওয়ার পরে wallet credit

Request Body:
{
    "payment_intent_id": "pi_xxx"
}

Response (200):
{
    "success": true,
    "message": "৳200 added to your wallet",
    "data": {
        "new_balance": "350.00"
    }
}

Logic:
- Stripe PaymentIntent retrieve করো (status: succeeded)
- WalletService::creditUser() — category: top_up
```

### POST /api/v1/webhook/stripe
```
Description: Stripe Webhook (Public — no auth)
Header: Stripe-Signature

Logic:
- Webhook signature verify করো
- Event type check:
  payment_intent.succeeded → wallet top-up process
  payment_intent.payment_failed → notify user
```

---

## ২. Driver Wallet APIs

### GET /api/v1/driver/wallet
```
Response:
{
    "success": true,
    "data": {
        "balance": "500.00",
        "due_amount": "0.00",
        "due_limit": "500.00",
        "can_accept_orders": true
    }
}
```

### GET /api/v1/driver/wallet/transactions
```
Same structure as customer wallet transactions
```

### POST /api/v1/driver/wallet/withdrawal/request
```
Request Body:
{
    "amount": 300.00,
    "method": "bkash",
    "account": "01712345678"
}

Response (200):
{
    "success": true,
    "message": "Withdrawal request submitted",
    "data": {
        "request_id": 5,
        "amount": "300.00",
        "status": "pending"
    }
}

Validation:
- amount >= withdrawal_minimum_amount (system setting)
- amount <= wallet_balance
- No pending withdrawal already exists
```

### GET /api/v1/driver/wallet/withdrawal/history
```
Response: List of withdrawal requests with status
```

---

## ৩. Invoice API

### GET /api/v1/orders/{orderId}/invoice
```
Middleware: auth:sanctum + auth.user

Response (200):
{
    "success": true,
    "data": {
        "invoice_number": "INV-RR-2024-00123",
        "order_number": "RR-2024-00123",
        "order_type": "ride",
        "date": "2024-01-15T10:30:00Z",
        "customer": { "name": "John", "phone": "017XXXXXXXX" },
        "driver": { "name": "Karim", "vehicle": "Honda CB150R" },
        "route": {
            "from": "Mirpur 10, Dhaka",
            "to": "Dhanmondi 27, Dhaka",
            "distance_km": "3.20",
            "duration_minutes": 15
        },
        "fare_breakdown": {
            "base_fare": "20.00",
            "distance_charge": "38.40",
            "time_charge": "15.00",
            "surge_amount": "0.00",
            "coupon_discount": "0.00",
            "tip": "0.00",
            "total": "73.40"
        },
        "payment": {
            "method": "cash",
            "status": "paid"
        }
    }
}
```

### GET /api/v1/orders/{orderId}/invoice/pdf
```
Description: PDF invoice download
Response: PDF file (application/pdf)

Logic: Laravel DomPDF বা similar দিয়ে generate
```

---

## Routes Update

```php
// Customer Payment
Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
    Route::get('user/wallet', [WalletController::class, 'balance']);
    Route::get('user/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('user/wallet/topup/initiate', [WalletController::class, 'initiateTopup']);
    Route::post('user/wallet/topup/confirm', [WalletController::class, 'confirmTopup']);
    Route::get('orders/{orderId}/invoice', [InvoiceController::class, 'show']);
    Route::get('orders/{orderId}/invoice/pdf', [InvoiceController::class, 'pdf']);
});

// Driver Payment
Route::prefix('driver')->middleware(['auth:sanctum', 'auth.driver'])->group(function () {
    Route::get('wallet', [DriverWalletController::class, 'balance']);
    Route::get('wallet/transactions', [DriverWalletController::class, 'transactions']);
    Route::post('wallet/withdrawal/request', [DriverWalletController::class, 'requestWithdrawal']);
    Route::get('wallet/withdrawal/history', [DriverWalletController::class, 'withdrawalHistory']);
});

// Stripe Webhook (Public)
Route::post('webhook/stripe', [StripeWebhookController::class, 'handle']);
```

---
---
---

# API Phase 5: History + Notifications + SOS + Profile

---

## Context
API Phase 1-4 শেষ। এই phase-এ History, Notifications, SOS, Profile features বানাবো।

---

## ১. Order History

### GET /api/v1/user/orders
```
Middleware: auth:sanctum + auth.user

Query Params:
- page, per_page
- type: all/ride/parcel
- status: all/completed/cancelled

Response (200):
{
    "success": true,
    "data": [
        {
            "id": 123,
            "order_number": "RR-2024-00123",
            "type": "ride",
            "status": "completed",
            "pickup_address": "Mirpur 10",
            "drop_address": "Dhanmondi 27",
            "total_amount": "73.40",
            "payment_method": "cash",
            "driver_name": "Karim",
            "driver_avatar": "https://...",
            "created_at": "2024-01-15T10:30:00Z",
            "completed_at": "2024-01-15T11:00:00Z"
        }
    ],
    "meta": { ...pagination... }
}
```

### GET /api/v1/user/orders/{orderId}
```
Full order details (same as admin but customer-facing)
```

### GET /api/v1/driver/orders
```
Same structure for driver order history
```

---

## ২. Favourite Locations

### GET /api/v1/user/favourite-locations
```
Response: List of saved locations
```

### POST /api/v1/user/favourite-locations
```
Request Body:
{
    "label": "home",        // home/office/other
    "custom_label": null,   // if other
    "address": "Mirpur 10, Dhaka",
    "lat": 23.8103,
    "lng": 90.4125
}
```

### PUT /api/v1/user/favourite-locations/{id}
### DELETE /api/v1/user/favourite-locations/{id}

---

## ৩. Notifications

### GET /api/v1/user/notifications
```
Query: page, per_page, unread_only

Response (200):
{
    "success": true,
    "data": {
        "unread_count": 3,
        "notifications": [
            {
                "id": 1,
                "title": "Ride Completed",
                "body": "Your ride to Dhanmondi has been completed",
                "type": "order_update",
                "data": { "order_id": 123 },
                "is_read": false,
                "created_at": "2024-01-15T11:00:00Z"
            }
        ]
    },
    "meta": { ...pagination... }
}
```

### POST /api/v1/user/notifications/mark-read
```
Request Body:
{
    "notification_ids": [1, 2, 3],  // specific IDs
    "mark_all": false               // or mark all
}
```

### GET /api/v1/driver/notifications
### POST /api/v1/driver/notifications/mark-read
```
Same structure for driver
```

---

## ৪. SOS

### POST /api/v1/user/sos
```
Middleware: auth:sanctum + auth.user

Request Body:
{
    "order_id": 123,  // optional
    "lat": 23.8103,
    "lng": 90.4125
}

Response (200):
{
    "success": true,
    "message": "SOS alert sent. Help is on the way."
}

Logic:
- sos_alerts table-এ insert
- Admin Pusher notification
- Emergency contact SMS (if set)
```

### POST /api/v1/driver/sos
```
Same structure for driver
```

---

## ৫. Referral

### GET /api/v1/user/referral
```
Response (200):
{
    "success": true,
    "data": {
        "referral_code": "JOHN1234",
        "share_message": "Use my code JOHN1234 on ReadyRide and get ৳30 bonus!",
        "total_referred": 5,
        "total_earned": "250.00",
        "referrals": [
            {
                "name": "Jane",
                "joined_at": "2024-01-10",
                "bonus_status": "rewarded",
                "bonus_amount": "50.00"
            }
        ]
    }
}
```

---

## ৬. Dispute/Complaint

### POST /api/v1/user/complaints
```
Request Body:
{
    "order_id": 123,
    "category": "overcharging",
    "description": "Driver charged extra"
}

Response (200):
{
    "success": true,
    "message": "Complaint submitted. We will review and respond within 24 hours.",
    "data": { "complaint_id": 5 }
}
```

### GET /api/v1/user/complaints
```
List of user's complaints with status
```

---

## ৭. Settings/Config API

### GET /api/v1/config
```
Description: App startup-এ একবার call করবে — সব settings পাবে
NO AUTH required

Response (200):
{
    "success": true,
    "data": {
        "app_name": "ReadyRide",
        "currency": "BDT",
        "currency_symbol": "৳",
        "ride_share_enabled": false,
        "surge_enabled": true,
        "scheduled_booking_enabled": true,
        "max_schedule_days": 7,
        "cod_enabled": true,
        "proof_of_delivery_enabled": true,
        "cancellation_fee_enabled": true,
        "cancellation_grace_minutes": 5,
        "cancellation_fee_amount": "30.00",
        "tip_enabled": true,
        "tip_amounts": [10, 20, 50, 100],
        "referral_enabled": true,
        "referrer_bonus": "50.00",
        "referee_bonus": "30.00",
        "request_timeout_seconds": 30,
        "google_maps_key": "AIza...",
        "pusher_key": "xxx",
        "pusher_cluster": "ap2"
    }
}
```

---

## Routes Update (Final)

```php
Route::prefix('v1')->group(function () {

    // Public
    Route::get('config', [ConfigController::class, 'index']);
    Route::get('parcel-track/{orderNumber}', [ParcelController::class, 'publicTracking']);
    Route::get('trip-share/{token}', [RideController::class, 'publicTracking']);
    Route::post('webhook/stripe', [StripeWebhookController::class, 'handle']);

    // Customer
    Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
        // History
        Route::get('user/orders', [OrderHistoryController::class, 'index']);
        Route::get('user/orders/{id}', [OrderHistoryController::class, 'show']);

        // Favourite Locations
        Route::apiResource('user/favourite-locations', FavouriteLocationController::class);

        // Notifications
        Route::get('user/notifications', [NotificationController::class, 'userIndex']);
        Route::post('user/notifications/mark-read', [NotificationController::class, 'markRead']);

        // SOS
        Route::post('user/sos', [SosController::class, 'userSos']);

        // Referral
        Route::get('user/referral', [ReferralController::class, 'index']);

        // Complaint
        Route::get('user/complaints', [ComplaintController::class, 'index']);
        Route::post('user/complaints', [ComplaintController::class, 'store']);
    });

    // Driver
    Route::prefix('driver')->middleware(['auth:sanctum', 'auth.driver'])->group(function () {
        // History
        Route::get('orders', [DriverOrderHistoryController::class, 'index']);
        Route::get('orders/{id}', [DriverOrderHistoryController::class, 'show']);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'driverIndex']);
        Route::post('notifications/mark-read', [NotificationController::class, 'driverMarkRead']);

        // SOS
        Route::post('sos', [SosController::class, 'driverSos']);

        // Earnings Summary
        Route::get('earnings', [DriverEarningsController::class, 'index']);
        Route::get('earnings/summary', [DriverEarningsController::class, 'summary']);
    });
});
```

---

## File Structure (All Phases Combined)

```
app/Http/Controllers/Api/
  Auth/
    CustomerAuthController.php
    DriverAuthController.php
  Customer/
    HomeController.php
    RideController.php
    ParcelController.php
    WalletController.php
    OrderHistoryController.php
    FavouriteLocationController.php
    NotificationController.php
    SosController.php
    ReferralController.php
    ComplaintController.php
    InvoiceController.php
    CustomerProfileController.php
  Driver/
    DriverProfileController.php
    DriverOnlineController.php
    DriverRideController.php
    DriverParcelController.php
    DriverWalletController.php
    DriverOrderHistoryController.php
    DriverEarningsController.php
  Shared/
    ConfigController.php
    StripeWebhookController.php
```

---

## গুরুত্বপূর্ণ নিয়ম

1. `/api/v1/config` response **Cache** করবে (5 মিনিট) — প্রতিটা app launch-এ call হবে
2. Notifications **paginate** করো — অনেক বেশি হতে পারে
3. SOS **rate limit** দাও — 3 per hour per user
4. Invoice PDF **queue** করো (background generate)
5. Driver earnings → daily/weekly/monthly **aggregate** করো

---

## শুরু করো এই order-এ

1. ConfigController
2. WalletController + DriverWalletController
3. StripeWebhookController
4. InvoiceController
5. OrderHistoryController + DriverOrderHistoryController
6. FavouriteLocationController
7. NotificationController
8. SosController
9. ReferralController
10. ComplaintController
11. DriverEarningsController
12. সব routes এক জায়গায় compile করো
13. Postman Collection তৈরি করো সব endpoints-এর জন্য
