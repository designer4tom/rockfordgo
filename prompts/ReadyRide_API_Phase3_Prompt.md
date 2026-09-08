# ReadyRide — Laravel API
# API Phase 3: Parcel Booking

---

## Context
API Phase 1 ও 2 শেষ — Auth, Ride Booking ready।
এই phase-এ Parcel Booking flow বানাবো।

---

## এই phase-এ যা করবে

1. Parcel Charge Estimate
2. Parcel Booking (Normal + COD)
3. Driver — Parcel Delivery Flow
4. COD Collection
5. Proof of Delivery

---

## ১. Parcel Estimate

### POST /api/v1/parcel/estimate
```
Middleware: auth:sanctum + auth.user

Request Body:
{
    "parcel_type": "normal",     // normal/fragile/document
    "weight": 0.5,               // kg
    "size": "small",             // small/medium/large
    "pickup_lat": 23.8103,
    "pickup_lng": 90.4125,
    "drop_lat": 23.7946,
    "drop_lng": 90.4075,
    "is_cod": false
}

Response (200):
{
    "success": true,
    "data": {
        "distance_km": 3.2,
        "delivery_charge": "85.00",
        "breakdown": {
            "base_charge": "50.00",
            "distance_charge": "35.00"
        },
        "payment_timing_options": ["before", "after"],  // admin setting অনুযায়ী
        "cod_available": true,
        "currency": "BDT"
    }
}
```

---

## ২. Parcel Booking

### POST /api/v1/parcel/book
```
Middleware: auth:sanctum + auth.user

Request Body:
{
    // Sender
    "sender_name": "John Doe",
    "sender_phone": "01712345678",
    "pickup_address": "Mirpur 10, Dhaka",
    "pickup_lat": 23.8103,
    "pickup_lng": 90.4125,

    // Receiver
    "receiver_name": "Jane Doe",
    "receiver_phone": "01812345678",
    "drop_address": "Dhanmondi 27, Dhaka",
    "drop_lat": 23.7946,
    "drop_lng": 90.4075,

    // Parcel
    "parcel_type": "normal",
    "weight": 0.5,
    "size": "small",
    "parcel_note": "Handle with care",
    "parcel_photo": [file],  // optional, multipart

    // COD
    "is_cod": true,
    "cod_amount": 500.00,    // required if is_cod=true

    // Payment
    "payment_timing": "before",   // before/after
    "payment_method": "wallet",   // cash/online/wallet (for delivery charge)
    "coupon_code": null
}

Response (200):
{
    "success": true,
    "message": "Parcel booking confirmed",
    "data": {
        "order_id": 124,
        "order_number": "RR-2024-00124",
        "status": "pending",
        "delivery_charge": "85.00",
        "cod_amount": "500.00",
        "total_receiver_pays": "585.00",  // cod_amount + delivery_charge (COD হলে)
        "payment_timing": "before",
        "payment_method": "wallet",
        "sender": {
            "name": "John Doe",
            "phone": "017XXXXXXXX"
        },
        "receiver": {
            "name": "Jane Doe",
            "phone": "018XXXXXXXX"
        }
    }
}

Response (400 — COD wallet insufficient):
{
    "success": false,
    "message": "No driver available with sufficient wallet balance for COD delivery."
}

Logic:
1. PricingService::calculateParcelCharge()
2. COD হলে: cod_amount validate করো
3. Before Pay হলে: payment process
4. Order create করো
5. DispatchOrderToDrivers Job (COD flag সহ)
   → COD drivers: wallet_balance >= cod_amount check করো
```

### GET /api/v1/parcel/{orderId}/status
```
Same structure as ride status
কিন্তু driver info-তে extra:
"cod_amount": "500.00",  // reminder
"proof_required": true
```

### POST /api/v1/parcel/{orderId}/cancel
```
Same as ride cancel
After Pay parcel cancel → no charge (delivery নেই তাই)
Before Pay cancel → refund to wallet
```

---

## ৩. Driver — Parcel Flow

### POST /api/v1/driver/parcel/update-status
```
Middleware: auth:sanctum + auth.driver

Request Body:
{
    "order_id": 124,
    "status": "picked_up"  // go_to_pickup/confirm_arrival/picked_up/start_ride/dropped_off/completed
}

Response (200):
{
    "success": true,
    "data": {
        "status": "picked_up",
        "next_action": "Deliver to receiver",
        "receiver": {
            "name": "Jane Doe",
            "phone": "018XXXXXXXX",
            "address": "Dhanmondi 27, Dhaka",
            "lat": 23.7946,
            "lng": 90.4075
        },
        "cod_reminder": {  // COD হলে
            "collect_amount": "585.00",
            "breakdown": "৳500 (product) + ৳85 (delivery)"
        }
    }
}
```

### POST /api/v1/driver/parcel/collect-cod
```
Description: Driver COD collect করেছে confirm করা

Request Body:
{
    "order_id": 124,
    "collected_amount": 585.00
}

Response (200):
{
    "success": true,
    "message": "COD collected. Complete delivery to finalize.",
    "data": {
        "collected": "585.00",
        "your_earning": "68.00",   // delivery charge driver portion
        "sender_payout": "415.00"  // cod_amount - delivery_charge admin portion
    }
}

Logic:
- collected_amount validate করো
- Order-এ cod_collected = true mark করো
- Complete করার পরে WalletService::processCodCollection()
```

### POST /api/v1/driver/parcel/complete
```
Description: Delivery complete (proof + payment)

Request Body (multipart):
{
    "order_id": 124,
    "proof_type": "otp",      // otp/photo/signature
    "proof_otp": "1234",
    "proof_photo": [file],    // if photo
    "proof_signature": "base64"  // if signature
}

Response (200):
{
    "success": true,
    "message": "Delivery completed!",
    "data": {
        "order_number": "RR-2024-00124",
        "your_earning": "68.00",
        "wallet_balance": "568.00"
    }
}

Logic:
1. Proof validate করো (admin setting অনুযায়ী)
2. After Pay হলে: receiver payment collect
3. COD হলে: WalletService::processCodCollection()
4. Driver earning credit
5. Sender-কে notification (product price wallet-এ এসেছে)
6. Order completed
```

---

## ৪. After Pay Collection (Customer side)

### POST /api/v1/parcel/{orderId}/pay-after-delivery
```
Description: After Pay parcel-এ delivery-র পরে Customer pay করবে
Middleware: auth:sanctum + auth.user

Request Body:
{
    "payment_method": "cash"  // cash/online/wallet
}

Response (200):
{
    "success": true,
    "message": "Payment successful"
}
```

---

## ৫. Parcel Tracking (Public — Receiver)

### GET /api/v1/parcel-track/{orderNumber}
```
Description: Receiver tracking link — NO AUTH
Query: phone=01812345678 (receiver phone verify)

Response (200):
{
    "success": true,
    "data": {
        "order_number": "RR-2024-00124",
        "status": "start_ride",
        "status_label": "On the way to you",
        "driver": {
            "name": "Karim",
            "current_lat": 23.8000,
            "current_lng": 90.4050
        },
        "estimated_arrival": 12,
        "parcel_type": "normal",
        "cod_amount": "500.00"  // receiver-কে দেখাবে
    }
}
```

---

## File Structure

```
app/Http/Controllers/Api/
  Customer/
    ParcelController.php
  Driver/
    DriverParcelController.php
```

## Routes Update

```php
// Customer
Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
    Route::post('parcel/estimate', [ParcelController::class, 'estimate']);
    Route::post('parcel/book', [ParcelController::class, 'book']);
    Route::get('parcel/{orderId}/status', [ParcelController::class, 'status']);
    Route::post('parcel/{orderId}/cancel', [ParcelController::class, 'cancel']);
    Route::post('parcel/{orderId}/pay-after-delivery', [ParcelController::class, 'payAfterDelivery']);
    Route::post('parcel/{orderId}/rate', [ParcelController::class, 'rate']);
});

// Public
Route::get('parcel-track/{orderNumber}', [ParcelController::class, 'publicTracking']);

// Driver
Route::prefix('driver')->middleware(['auth:sanctum', 'auth.driver'])->group(function () {
    Route::post('parcel/update-status', [DriverParcelController::class, 'updateStatus']);
    Route::post('parcel/collect-cod', [DriverParcelController::class, 'collectCod']);
    Route::post('parcel/complete', [DriverParcelController::class, 'complete']);
});
```

---

## গুরুত্বপূর্ণ নিয়ম

1. COD order dispatch-এ শুধু সেই drivers পাবে যাদের wallet >= cod_amount
2. Proof required হলে complete করা যাবে না proof ছাড়া
3. After Pay-এ driver complete করার আগে payment collect confirm লাগবে
4. COD payout sender wallet-এ যাবে automatically complete-এ

---

## শুরু করো এই order-এ

1. ParcelController (estimate, book, status, cancel, pay, rate, tracking)
2. DriverParcelController (update-status, collect-cod, complete)
3. DispatchOrderToDrivers Job update (COD wallet check)
4. Routes update
5. Test: Book COD parcel → Driver accept → Pickup → Deliver → COD collect → Complete
