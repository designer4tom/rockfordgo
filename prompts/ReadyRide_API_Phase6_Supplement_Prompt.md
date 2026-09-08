# ReadyRide — Laravel API
# API Phase 6 (Supplement): Missing Endpoints

---

## Context
Cross-check করে দেখা গেছে কিছু endpoint বাদ পড়েছে।
আগের সব API phase (1-5) build করার পরে এই endpoints যোগ করবে।

---

## ⚠️ Missing Endpoints যা যোগ করতে হবে

### ১. Coupon Validate (CRITICAL — Booking-এ লাগবে)
```
POST /api/v1/coupon/validate
Middleware: auth:sanctum + auth.user

Request Body:
{
    "code": "SAVE20",
    "service_type": "ride",      // ride/parcel
    "order_amount": 100.00
}

Response (200 — Valid):
{
    "success": true,
    "data": {
        "valid": true,
        "code": "SAVE20",
        "discount_type": "percentage",
        "discount_value": "20.00",
        "discount_amount": "20.00",   // actual amount on this order
        "max_discount": "50.00",
        "final_amount": "80.00"
    }
}

Response (422 — Invalid):
{
    "success": false,
    "message": "Invalid or expired coupon code"
}

Validation checks:
- Coupon exists ও active
- valid_from <= today <= valid_until
- service_type matches (or 'all')
- order_amount >= min_order_amount
- usage_limit not exceeded
- per_user_limit not exceeded (this user)
```

### ২. Available Coupons List
```
GET /api/v1/coupons
Middleware: auth:sanctum + auth.user

Query: service_type (optional)

Response:
{
    "success": true,
    "data": [
        {
            "code": "SAVE20",
            "description": "20% off on rides",
            "discount_type": "percentage",
            "discount_value": "20.00",
            "max_discount": "50.00",
            "min_order_amount": "100.00",
            "valid_until": "2024-12-31"
        }
    ]
}

Logic: Active coupons যা user এখনো ব্যবহার করতে পারবে
```

### ৩. Parcel Rate (Customer → Driver)
```
POST /api/v1/parcel/{orderId}/rate
Middleware: auth:sanctum + auth.user

Request Body:
{
    "rating": 5,
    "comment": "Fast delivery",
    "tags": ["on_time", "good_behavior"]
}

Response (200):
{
    "success": true,
    "message": "Rating submitted"
}

(এটা ride rate-এর মতোই, কিন্তু parcel order-এর জন্য)
```

### ৪. Driver Earnings APIs
```
GET /api/v1/driver/earnings
Middleware: auth:sanctum + auth.driver

Query: period (today/week/month/all)

Response (200):
{
    "success": true,
    "data": {
        "period": "today",
        "total_earning": "450.00",
        "total_trips": 8,
        "ride_earning": "320.00",
        "parcel_earning": "130.00",
        "commission_paid": "75.00",
        "tips_received": "40.00",
        "online_hours": "6.5"
    }
}

GET /api/v1/driver/earnings/summary
Response (200):
{
    "success": true,
    "data": {
        "today": { "earning": "450.00", "trips": 8 },
        "this_week": { "earning": "2800.00", "trips": 45 },
        "this_month": { "earning": "12400.00", "trips": 198 },
        "lifetime": { "earning": "85600.00", "trips": 1250 }
    }
}

GET /api/v1/driver/earnings/chart
Query: period (week/month)
Response: Daily breakdown for chart
{
    "success": true,
    "data": [
        { "date": "2024-01-15", "earning": "450.00", "trips": 8 },
        { "date": "2024-01-14", "earning": "380.00", "trips": 6 }
    ]
}
```

### ৫. Driver Performance Stats
```
GET /api/v1/driver/performance
Middleware: auth:sanctum + auth.driver

Response (200):
{
    "success": true,
    "data": {
        "average_rating": "4.80",
        "total_ratings": 245,
        "rating_breakdown": {
            "5": 180, "4": 50, "3": 10, "2": 3, "1": 2
        },
        "acceptance_rate": "92.50",
        "completion_rate": "98.00",
        "cancellation_rate": "2.00",
        "recent_ratings": [
            {
                "rating": 5,
                "comment": "Great driver",
                "customer_name": "John",
                "created_at": "2024-01-15"
            }
        ]
    }
}
```

### ৬. Driver Shift History
```
GET /api/v1/driver/shifts
Middleware: auth:sanctum + auth.driver

Query: page, per_page

Response (200):
{
    "success": true,
    "data": [
        {
            "date": "2024-01-15",
            "went_online": "08:00",
            "went_offline": "14:30",
            "total_hours": "6.5",
            "trips": 8,
            "earning": "450.00"
        }
    ],
    "meta": { ...pagination... }
}
```

### ৭. Driver Complaints
```
GET /api/v1/driver/complaints
POST /api/v1/driver/complaints
Middleware: auth:sanctum + auth.driver

(Customer complaint-এর মতোই কিন্তু driver-এর জন্য)
```

### ৮. Scheduled Orders List (Customer)
```
GET /api/v1/user/scheduled-orders
Middleware: auth:sanctum + auth.user

Response (200):
{
    "success": true,
    "data": [
        {
            "order_id": 130,
            "order_number": "RR-2024-00130",
            "type": "ride",
            "scheduled_at": "2024-01-16T09:00:00Z",
            "pickup_address": "Mirpur 10",
            "drop_address": "Airport",
            "vehicle_category": "Car",
            "estimated_fare": "350.00",
            "status": "scheduled"
        }
    ]
}

POST /api/v1/user/scheduled-orders/{orderId}/cancel
Cancel scheduled order (fee নাও লাগতে পারে — admin setting)
```

### ৯. Emergency Contact Management
```
GET /api/v1/user/emergency-contact
POST /api/v1/user/emergency-contact
Middleware: auth:sanctum + auth.user

POST Request Body:
{
    "name": "Family Member",
    "phone": "01712345678",
    "relationship": "Brother"
}

Response (200):
{
    "success": true,
    "message": "Emergency contact saved"
}

Migration add করো:
ALTER TABLE users ADD COLUMN emergency_contact_name VARCHAR nullable;
ALTER TABLE users ADD COLUMN emergency_contact_phone VARCHAR nullable;
ALTER TABLE users ADD COLUMN emergency_contact_relationship VARCHAR nullable;

(Driver-এর জন্যও same: /api/v1/driver/emergency-contact)
```

### ১০. Account Deletion
```
DELETE /api/v1/user/account
Middleware: auth:sanctum + auth.user

Request Body:
{
    "reason": "Not using anymore"  // optional
}

Response (200):
{
    "success": true,
    "message": "Account deletion requested. Your account will be deactivated."
}

Logic:
- Active orders আছে কিনা check — থাকলে block
- Soft approach: is_active = false, anonymize data
- Hard delete: tokens revoke, mark for deletion
- (App Store/Play Store requirement — must have this)

DELETE /api/v1/driver/account
Same for driver — pending dues check করবে
```

### ১১. Help/FAQ ও Support
```
GET /api/v1/faqs
Query: category (general/ride/parcel/payment)
NO AUTH

Response (200):
{
    "success": true,
    "data": [
        {
            "question": "How do I book a ride?",
            "answer": "Open the app, select Ride...",
            "category": "ride"
        }
    ]
}

GET /api/v1/pages/{slug}
slug: privacy-policy / terms-conditions / about-us
NO AUTH

Response:
{
    "success": true,
    "data": {
        "title": "Privacy Policy",
        "content": "...",  // HTML content from landing_page CMS
        "updated_at": "2024-01-01"
    }
}
```

### ১২. Cancellation Reasons List
```
GET /api/v1/cancellation-reasons
Query: type (ride/parcel), by (user/driver)
NO AUTH

Response (200):
{
    "success": true,
    "data": [
        "Driver is taking too long",
        "Found another ride",
        "Changed my mind",
        "Wrong pickup location",
        "Other"
    ]
}

(Predefined reasons — hardcoded বা settings table-এ)
```

### ১৩. Search Address (Geocoding proxy)
```
GET /api/v1/geocode/search
Query: q (search query), lat, lng (bias)
Middleware: auth:sanctum

Response (200):
{
    "success": true,
    "data": [
        {
            "address": "Mirpur 10 Circle, Dhaka",
            "lat": 23.8069,
            "lng": 90.3687,
            "place_id": "xxx"
        }
    ]
}

Logic: Google Places Autocomplete API proxy
(Direct app থেকে call করলে API key expose হবে — তাই backend proxy)

GET /api/v1/geocode/reverse
Query: lat, lng
Response: address from coordinates
```

---

## Routes Update (Supplement)

```php
Route::prefix('v1')->group(function () {

    // Public
    Route::get('faqs', [FaqController::class, 'index']);
    Route::get('pages/{slug}', [PageController::class, 'show']);
    Route::get('cancellation-reasons', [ConfigController::class, 'cancellationReasons']);

    // Customer
    Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
        Route::post('coupon/validate', [CouponController::class, 'validate']);
        Route::get('coupons', [CouponController::class, 'index']);
        Route::post('parcel/{orderId}/rate', [ParcelController::class, 'rate']);
        Route::get('user/scheduled-orders', [ScheduledOrderController::class, 'index']);
        Route::post('user/scheduled-orders/{orderId}/cancel', [ScheduledOrderController::class, 'cancel']);
        Route::get('user/emergency-contact', [EmergencyContactController::class, 'show']);
        Route::post('user/emergency-contact', [EmergencyContactController::class, 'store']);
        Route::delete('user/account', [CustomerProfileController::class, 'deleteAccount']);
        Route::get('geocode/search', [GeocodeController::class, 'search']);
        Route::get('geocode/reverse', [GeocodeController::class, 'reverse']);
    });

    // Driver
    Route::prefix('driver')->middleware(['auth:sanctum', 'auth.driver'])->group(function () {
        Route::get('earnings', [DriverEarningsController::class, 'index']);
        Route::get('earnings/summary', [DriverEarningsController::class, 'summary']);
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
```

---

## Migration (Emergency Contact + Account Deletion)

```php
// add_emergency_contact_to_users_and_drivers
Schema::table('users', function (Blueprint $table) {
    $table->string('emergency_contact_name')->nullable();
    $table->string('emergency_contact_phone')->nullable();
    $table->string('emergency_contact_relationship')->nullable();
    $table->timestamp('deletion_requested_at')->nullable();
});

Schema::table('drivers', function (Blueprint $table) {
    $table->string('emergency_contact_name')->nullable();
    $table->string('emergency_contact_phone')->nullable();
    $table->string('emergency_contact_relationship')->nullable();
    $table->timestamp('deletion_requested_at')->nullable();
});

// add_share_token_to_orders (Phase 2-এ মিস হয়ে থাকলে)
Schema::table('orders', function (Blueprint $table) {
    $table->string('share_token')->nullable()->unique();
    $table->timestamp('share_token_expires_at')->nullable();
});
```

---

## File Structure (Supplement)

```
app/Http/Controllers/Api/
  Customer/
    CouponController.php
    ScheduledOrderController.php
    EmergencyContactController.php
    GeocodeController.php
  Driver/
    DriverEarningsController.php
    DriverPerformanceController.php
    DriverShiftController.php
    DriverComplaintController.php
  Shared/
    FaqController.php
    PageController.php
```

---

## ✅ Complete API Checklist (সব মিলিয়ে)

### Auth (Customer + Driver)
- [x] Send OTP, Verify OTP, Complete Profile
- [x] Driver Register, Status, Logout
- [x] Profile show/update, FCM token

### Home & Discovery
- [x] Services, Vehicle Categories, Nearby Drivers
- [x] Fare Estimate, Parcel Estimate
- [x] Config, FAQs, Pages, Cancellation Reasons
- [x] Geocode Search/Reverse

### Ride
- [x] Book, Status, Cancel, Tip, Rate, Share Link
- [x] Driver: Respond, Update Status, Collect Proof

### Parcel
- [x] Estimate, Book, Status, Cancel, Pay After, Rate
- [x] Driver: Update Status, Collect COD, Complete

### Coupon
- [x] Validate, List

### Payment & Wallet
- [x] Wallet Balance, Transactions
- [x] Top-up Initiate/Confirm, Stripe Webhook
- [x] Invoice, Invoice PDF
- [x] Driver Withdrawal Request/History

### Tracking (Real-time)
- [x] Driver Toggle Online, Update Location, Active Order
- [x] Public Trip Share, Parcel Track
- [x] Pusher channels

### History
- [x] User Orders List/Detail
- [x] Driver Orders List/Detail
- [x] Scheduled Orders

### Driver Specific
- [x] Earnings (today/week/month/chart)
- [x] Performance Stats
- [x] Shift History

### Engagement
- [x] Notifications List/Mark Read
- [x] Referral
- [x] Favourite Locations CRUD
- [x] Emergency Contact
- [x] Complaints (User + Driver)
- [x] SOS (User + Driver)

### Account
- [x] Delete Account (App Store requirement)

---

## শুরু করো এই order-এ

1. CouponController (validate, list) — সবচেয়ে important, booking-এ লাগবে
2. GeocodeController (search, reverse)
3. DriverEarningsController
4. DriverPerformanceController + DriverShiftController
5. ScheduledOrderController
6. EmergencyContactController
7. FaqController + PageController
8. Account deletion methods
9. Parcel rate method
10. Migrations (emergency contact, share token)
11. Routes update
12. Final Postman Collection update
