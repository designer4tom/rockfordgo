# ReadyRide — Laravel API
# API Phase 2: Home + Services + Ride Booking

---

## Context
API Phase 1 শেষ — Auth, OTP, Profile ready।
এই phase-এ Home screen data, Ride booking flow, Fare calculation API বানাবো।

---

## এই phase-এ যা করবে

1. Home Screen APIs (Services, Vehicle Categories, Nearby Drivers)
2. Fare Estimate API
3. Ride Booking Flow (Book → Track → Complete)
4. Driver — Ride Request Handle (Accept/Reject)
5. Driver — Online/Offline Toggle

---

## ১. Home Screen APIs

### GET /api/v1/services
```
Description: Available services list

Response (200):
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Ride",
            "slug": "ride",
            "type": "ride",
            "icon": "https://domain.com/storage/services/ride.png",
            "description": "Comfortable rides at your fingertips"
        },
        {
            "id": 2,
            "name": "Parcel",
            "slug": "parcel",
            "type": "parcel",
            "icon": "https://domain.com/storage/services/parcel.png",
            "description": "Send parcels anywhere in the city"
        }
    ]
}

Logic: Service::where('is_active', true)->orderBy('sort_order')->get()
```

### GET /api/v1/ride/vehicle-categories
```
Description: Ride-এর জন্য available vehicle categories

Query Params:
- pickup_lat: required
- pickup_lng: required

Response (200):
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Bike",
            "icon": "https://...",
            "capacity": 1,
            "base_fare": "20.00",
            "per_km_rate": "12.00",
            "per_minute_rate": "1.00",
            "minimum_fare": "40.00",
            "estimated_arrival": 5,  // minutes (nearest driver থেকে calculate)
            "available_drivers": 3,  // nearby drivers count
            "surge_active": false,
            "surge_multiplier": 1.0
        },
        ...
    ]
}

Logic:
- VehicleCategory::active() for ride service
- প্রতিটা category-র জন্য nearby drivers count
- Surge check করো (SurgePricingRule)
```

### POST /api/v1/ride/fare-estimate
```
Description: Trip শুরুর আগে fare estimate দেখানো

Request Body:
{
    "vehicle_category_id": 1,
    "pickup_lat": 23.8103,
    "pickup_lng": 90.4125,
    "drop_lat": 23.7946,
    "drop_lng": 90.4075,
    "stops": [    // optional multi-stop
        {"lat": 23.8050, "lng": 90.4100, "address": "Stop 1"}
    ]
}

Response (200):
{
    "success": true,
    "data": {
        "vehicle_category_id": 1,
        "vehicle_category_name": "Bike",
        "distance_km": 3.2,
        "duration_minutes": 15,
        "breakdown": {
            "base_fare": "20.00",
            "distance_charge": "38.40",
            "time_charge": "15.00",
            "surge_amount": "0.00",
            "surge_multiplier": 1.0
        },
        "total_fare": "73.40",
        "minimum_fare": "40.00",
        "final_fare": "73.40",
        "surge_active": false,
        "currency": "BDT"
    }
}

Logic:
- Google Maps Distance Matrix API দিয়ে distance/duration calculate
- PricingService::calculateRideFare() call করো
- Surge check করো
```

### GET /api/v1/nearby-drivers
```
Description: Map-এ nearby drivers দেখানো (real-time)

Query Params:
- lat: required
- lng: required
- radius: optional (default: 5km)
- vehicle_category_id: optional

Response (200):
{
    "success": true,
    "data": [
        {
            "id": 1,
            "lat": 23.8110,
            "lng": 90.4130,
            "vehicle_category": "Bike",
            "bearing": 45  // direction (degrees) for icon rotation
        },
        ...
    ]
}

Note: শুধু lat/lng দেবে — privacy-র জন্য driver details দেবে না
```

---

## ২. Ride Booking

### POST /api/v1/ride/book
```
Middleware: auth:sanctum + auth.user

Request Body:
{
    "vehicle_category_id": 1,
    "pickup_address": "Mirpur 10, Dhaka",
    "pickup_lat": 23.8103,
    "pickup_lng": 90.4125,
    "drop_address": "Dhanmondi 27, Dhaka",
    "drop_lat": 23.7946,
    "drop_lng": 90.4075,
    "stops": [],  // optional
    "payment_method": "cash",  // cash/online/wallet
    "coupon_code": "SAVE20",   // optional
    "scheduled_at": null,       // optional datetime for scheduled booking
    "ride_share": false         // optional
}

Response (200):
{
    "success": true,
    "message": "Booking confirmed. Looking for driver...",
    "data": {
        "order_id": 123,
        "order_number": "RR-2024-00123",
        "status": "pending",
        "otp": "4521",  // Customer দেখবে, Driver-কে বলবে
        "fare": {
            "base_fare": "20.00",
            "distance_charge": "38.40",
            "time_charge": "15.00",
            "surge_amount": "0.00",
            "coupon_discount": "10.00",
            "total": "63.40"
        },
        "payment_method": "cash",
        "pickup": {
            "address": "Mirpur 10, Dhaka",
            "lat": 23.8103,
            "lng": 90.4125
        },
        "drop": {
            "address": "Dhanmondi 27, Dhaka",
            "lat": 23.7946,
            "lng": 90.4075
        }
    }
}

Response (400 — Wallet insufficient):
{
    "success": false,
    "message": "Insufficient wallet balance. Please top up or choose another payment method."
}

Logic:
1. Validate input
2. Coupon validate করো (যদি থাকে)
3. Before Pay হলে payment process করো
4. Order create করো (status: pending)
5. OTP generate করো (6 digits)
6. DispatchOrderToDrivers Job dispatch করো (Queue)
7. Response return করো
```

### GET /api/v1/ride/{orderId}/status
```
Description: Order current status polling
Middleware: auth:sanctum + auth.user

Response (200 — Searching):
{
    "success": true,
    "data": {
        "status": "pending",
        "message": "Looking for driver...",
        "driver": null
    }
}

Response (200 — Driver Found):
{
    "success": true,
    "data": {
        "status": "accepted",
        "message": "Driver is on the way",
        "driver": {
            "id": 5,
            "name": "Karim",
            "phone": "01812345678",
            "avatar": "https://...",
            "rating": "4.8",
            "vehicle": {
                "make": "Honda",
                "model": "CB150R",
                "color": "Red",
                "registration_number": "DHA-1234",
                "category": "Bike"
            },
            "current_lat": 23.8115,
            "current_lng": 90.4128,
            "estimated_arrival": 4  // minutes
        },
        "otp": "4521"
    }
}

Response (200 — No Driver Found):
{
    "success": true,
    "data": {
        "status": "no_driver_found",
        "message": "No driver available. Please try again."
    }
}
```

### POST /api/v1/ride/{orderId}/cancel
```
Middleware: auth:sanctum + auth.user

Request Body:
{
    "reason": "Changed my mind"
}

Response (200):
{
    "success": true,
    "message": "Ride cancelled",
    "data": {
        "cancellation_fee": "0.00",
        "refund_amount": "0.00"
    }
}

Response (200 — With Fee):
{
    "success": true,
    "message": "Ride cancelled with cancellation fee",
    "data": {
        "cancellation_fee": "30.00",
        "refund_amount": "33.40"  // paid - fee
    }
}

Logic:
- Order cancellable status check (pending/accepted/go_to_pickup)
- Cancellation fee calculate করো
- Refund যদি paid online/wallet
```

### POST /api/v1/ride/{orderId}/tip
```
Description: Trip complete-র পরে tip দেওয়া
Middleware: auth:sanctum + auth.user

Request Body:
{
    "amount": 20.00
}

Response (200):
{
    "success": true,
    "message": "Tip added successfully"
}

Logic:
- Order status must be completed
- Wallet থেকে deduct (বা cash tip confirm করা)
- Driver wallet-এ credit
```

---

## ৩. Driver — Ride Request Handle

### POST /api/v1/driver/ride/respond
```
Description: Order request Accept বা Reject
Middleware: auth:sanctum + auth.driver

Request Body:
{
    "order_id": 123,
    "action": "accept"  // accept/reject
}

Response (200 — Accepted):
{
    "success": true,
    "message": "Order accepted",
    "data": {
        "order_id": 123,
        "order_number": "RR-2024-00123",
        "status": "accepted",
        "customer": {
            "name": "John",
            "phone": "017XXXXXXXX"
        },
        "pickup": {
            "address": "Mirpur 10, Dhaka",
            "lat": 23.8103,
            "lng": 90.4125
        },
        "drop": {
            "address": "Dhanmondi 27, Dhaka",
            "lat": 23.7946,
            "lng": 90.4075
        },
        "fare": { ... },
        "payment_method": "cash"
    }
}

Response (400 — Already taken):
{
    "success": false,
    "message": "This order has already been taken by another driver"
}

Logic:
- Cache key "order_response_{orderId}_{driverId}" set করো
- Accept: order.driver_id = driver, status = accepted
- Customer-কে Pusher notification
```

### POST /api/v1/driver/ride/update-status
```
Description: Driver-এর trip status update
Middleware: auth:sanctum + auth.driver

Request Body:
{
    "order_id": 123,
    "status": "go_to_pickup",  // go_to_pickup/confirm_arrival/picked_up/start_ride/dropped_off/completed
    "otp": "4521"  // required only for picked_up status
}

Response (200):
{
    "success": true,
    "message": "Status updated",
    "data": {
        "order_id": 123,
        "status": "go_to_pickup",
        "next_action": "Press 'Arrived' when you reach pickup point"
    }
}

Response (422 — Wrong OTP):
{
    "success": false,
    "message": "Invalid OTP. Please ask the customer for the correct OTP."
}

Status Flow:
accepted → go_to_pickup → confirm_arrival → picked_up (OTP) → start_ride → dropped_off → completed

Logic per status:
- go_to_pickup: update status, customer notify
- confirm_arrival: update arrived_at, customer notify
- picked_up: OTP verify, update picked_up_at
- start_ride: update started_at
- dropped_off: update dropped_at, proof collect (if enabled)
- completed:
    → WalletService::processTripCommission(order)
    → Driver rating prompt
    → update completed_at
    → Customer notify
```

### POST /api/v1/driver/ride/collect-proof
```
Description: Parcel delivery proof collect করা
Middleware: auth:sanctum + auth.driver

Request Body (multipart):
{
    "order_id": 123,
    "proof_type": "photo",  // otp/photo/signature
    "otp": "1234",          // if type=otp
    "photo": [file],        // if type=photo
    "signature": "base64"   // if type=signature
}

Response (200):
{
    "success": true,
    "message": "Proof collected successfully"
}
```

---

## ৪. Driver Online/Offline ও Location

### POST /api/v1/driver/toggle-online
```
Middleware: auth:sanctum + auth.driver

Request Body:
{
    "is_online": true,
    "lat": 23.8103,
    "lng": 90.4125,
    "zone_id": 1  // optional
}

Response (200):
{
    "success": true,
    "data": {
        "is_online": true,
        "message": "You are now online"
    }
}

Response (403 — Cannot go online):
{
    "success": false,
    "message": "You cannot go online. Reason: [reason]",
    "reasons": [
        "driving_license document is expired",
        "Due amount exceeds limit"
    ]
}

Logic:
- Document expiry check
- Due limit check
- Update is_online, log shift
- If offline: close shift log
```

### POST /api/v1/driver/update-location
```
Description: Driver location real-time update (app প্রতি 3-5 sec call করবে)
Middleware: auth:sanctum + auth.driver

Request Body:
{
    "lat": 23.8103,
    "lng": 90.4125,
    "bearing": 45,   // direction
    "speed": 30      // km/h optional
}

Response (200):
{
    "success": true
}

Logic:
- drivers.current_lat, current_lng, last_location_at update
- Active order থাকলে order_locations-এ insert
- Pusher: driver-location.{orderId} channel broadcast
  (Customer app এই channel listen করবে)
```

### GET /api/v1/driver/active-order
```
Description: Driver-এর current active order
Middleware: auth:sanctum + auth.driver

Response (200 — Has active order):
{
    "success": true,
    "data": {
        "order_id": 123,
        "status": "start_ride",
        "type": "ride",
        "customer": { "name": "John", "phone": "017XXXXXXXX" },
        "pickup": { "address": "...", "lat": X, "lng": X },
        "drop": { "address": "...", "lat": X, "lng": X },
        "otp": "4521",
        "fare": { ... }
    }
}

Response (200 — No active order):
{
    "success": true,
    "data": null
}
```

---

## ৫. Rating

### POST /api/v1/ride/{orderId}/rate
```
Description: Customer → Driver rating দেওয়া
Middleware: auth:sanctum + auth.user

Request Body:
{
    "rating": 5,
    "comment": "Great driver!",
    "tags": ["on_time", "good_behavior", "clean_vehicle"]
}

Response (200):
{
    "success": true,
    "message": "Rating submitted"
}

Logic:
- ratings table-এ insert
- Driver average_rating recalculate করো
```

### POST /api/v1/driver/ride/{orderId}/rate
```
Description: Driver → Customer rating
Same structure
```

---

## ৬. Trip Share (Public)

### GET /api/v1/trip-share/{token}
```
Description: Public tracking link — NO AUTH required

Response (200):
{
    "success": true,
    "data": {
        "order_number": "RR-2024-00123",
        "status": "start_ride",
        "driver": {
            "name": "Karim",
            "vehicle": "Honda CB150R (Red) - DHA-1234",
            "current_lat": 23.8110,
            "current_lng": 90.4128
        },
        "pickup": { "address": "...", "lat": X, "lng": X },
        "drop": { "address": "...", "lat": X, "lng": X },
        "estimated_arrival": 8
    }
}

Response (404 — Expired/Not found):
{
    "success": false,
    "message": "This tracking link has expired or is invalid"
}
```

### POST /api/v1/ride/{orderId}/generate-share-link
```
Middleware: auth:sanctum + auth.user

Response (200):
{
    "success": true,
    "data": {
        "share_url": "https://readyride.com/track/abc123xyz",
        "expires_at": "2024-01-15T12:30:00Z"
    }
}

Logic:
- Random token generate
- orders table-এ share_token, share_token_expires_at store করো
  (migration add করো: share_token varchar nullable, share_token_expires_at timestamp nullable)
```

---

## ৭. Pusher Channels (Real-time)

```
Customer App যে channels listen করবে:
- private-order.{orderId}
  Events:
    → DriverAccepted (driver info)
    → DriverLocationUpdated (lat, lng, bearing)
    → StatusUpdated (new status)
    → OrderCompleted (fare breakdown)
    → OrderCancelled (reason)

Driver App যে channels listen করবে:
- private-driver.{driverId}
  Events:
    → NewOrderRequest (order details, 30s timer)
    → OrderCancelled (customer cancelled)

Broadcasting Events (app/Events/):
- OrderAcceptedEvent
- DriverLocationUpdatedEvent
- OrderStatusUpdatedEvent
- OrderCompletedEvent
- NewOrderRequestEvent
```

---

## ৮. File Structure

```
app/Http/Controllers/Api/
  Customer/
    HomeController.php         ← services, vehicle categories, nearby drivers
    RideController.php         ← book, status, cancel, tip, rate, share
  Driver/
    DriverOnlineController.php ← toggle online, update location, active order
    DriverRideController.php   ← respond, update-status, collect-proof, rate

app/Events/
  OrderAcceptedEvent.php
  DriverLocationUpdatedEvent.php
  OrderStatusUpdatedEvent.php
  NewOrderRequestEvent.php

app/Jobs/
  DispatchOrderToDrivers.php   ← Phase 8 থেকে (update করো)
```

---

## Routes Update (api.php)

```php
// Customer — Home & Ride
Route::prefix('v1')->group(function () {

    // Public
    Route::get('services', [HomeController::class, 'services']);
    Route::get('ride/vehicle-categories', [HomeController::class, 'vehicleCategories']);
    Route::post('ride/fare-estimate', [HomeController::class, 'fareEstimate']);
    Route::get('nearby-drivers', [HomeController::class, 'nearbyDrivers']);
    Route::get('trip-share/{token}', [RideController::class, 'publicTracking']);

    // Customer Protected
    Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
        Route::post('ride/book', [RideController::class, 'book']);
        Route::get('ride/{orderId}/status', [RideController::class, 'status']);
        Route::post('ride/{orderId}/cancel', [RideController::class, 'cancel']);
        Route::post('ride/{orderId}/tip', [RideController::class, 'addTip']);
        Route::post('ride/{orderId}/rate', [RideController::class, 'rate']);
        Route::post('ride/{orderId}/generate-share-link', [RideController::class, 'generateShareLink']);
    });

    // Driver Protected
    Route::prefix('driver')->middleware(['auth:sanctum', 'auth.driver'])->group(function () {
        Route::post('toggle-online', [DriverOnlineController::class, 'toggleOnline']);
        Route::post('update-location', [DriverOnlineController::class, 'updateLocation']);
        Route::get('active-order', [DriverOnlineController::class, 'activeOrder']);

        Route::post('ride/respond', [DriverRideController::class, 'respond']);
        Route::post('ride/update-status', [DriverRideController::class, 'updateStatus']);
        Route::post('ride/collect-proof', [DriverRideController::class, 'collectProof']);
        Route::post('ride/{orderId}/rate', [DriverRideController::class, 'rate']);
    });
});
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **update-location** endpoint — rate limit দাও: 60 requests/minute per driver
2. **nearby-drivers** — Cache করো (10 seconds): `Cache::remember('nearby_drivers_...', 10, ...)`
3. **fare-estimate** — Google Maps API call Cache করো (5 min same route)
4. **DispatchOrderToDrivers** Job-এ driver-এর due_limit check করো
5. Trip share token — UUID v4, expire: trip complete + 1 hour
6. Location update → Pusher broadcast করো শুধু active order থাকলে

---

## শুরু করো এই order-এ

1. Broadcasting setup (config/broadcasting.php + Pusher)
2. Events তৈরি করো
3. HomeController (services, categories, nearby, fare)
4. RideController (book, status, cancel, tip, rate, share)
5. DriverOnlineController
6. DriverRideController
7. DispatchOrderToDrivers Job update
8. Routes update
9. Test: Book ride → Driver accept → Status update → Complete
