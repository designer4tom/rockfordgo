# ReadyRide — Laravel API
# API Phase 1: Project Setup + Authentication

---

## Context
ReadyRide Laravel project already built (Admin Panel ready)।
এই phase-এ Customer ও Driver App-এর জন্য REST API বানাবো।
API prefix: `/api/v1/`
Auth: Laravel Sanctum (Token-based)

---

## এই phase-এ যা করবে

1. API Foundation Setup
2. Customer Auth (OTP Login)
3. Driver Auth (OTP Login)
4. Profile APIs
5. API Response Format standardize করা

---

## ১. API Foundation Setup

### ১.১ Sanctum Install ও Configure
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### ১.২ config/sanctum.php
```php
// Token expiry: 1 year (mobile app)
'expiration' => 525600,  // minutes
```

### ১.৩ Models Update
```php
// User model — HasApiTokens add
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
}

// Driver model — HasApiTokens add
class Driver extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
}
```

### ১.৪ Auth Guards (config/auth.php)
```php
'guards' => [
    'web' => [...],
    'admin' => [...],  // already exists
    'api_user' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
    'api_driver' => [
        'driver' => 'sanctum',
        'provider' => 'drivers',
    ],
],
'providers' => [
    'users' => ['driver' => 'eloquent', 'model' => User::class],
    'drivers' => ['driver' => 'eloquent', 'model' => Driver::class],
    'admins' => ['driver' => 'eloquent', 'model' => Admin::class],
],
```

### ১.৫ Standard API Response Format
```php
// app/Traits/ApiResponse.php

trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    protected function paginated($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
```

### ১.৬ API Middleware
```php
// app/Http/Middleware/AuthenticateUser.php
// Customer token verify করবে
public function handle($request, Closure $next)
{
    if (!$request->user('sanctum') instanceof User) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    return $next($request);
}

// app/Http/Middleware/AuthenticateDriver.php
// Driver token verify করবে
public function handle($request, Closure $next)
{
    if (!$request->user('sanctum') instanceof Driver) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    // Driver approved কিনা check
    if ($request->user('sanctum')->status !== 'approved') {
        return response()->json([
            'success' => false,
            'message' => 'Your account is not approved yet.',
            'status'  => $request->user('sanctum')->status,
        ], 403);
    }
    return $next($request);
}
```

### ১.৭ OTP Service
```php
// app/Services/OtpService.php

class OtpService
{
    // OTP generate ও send করা
    public function send(string $phone, string $type = 'user'): bool
    {
        $otp = rand(100000, 999999);
        $key = "otp_{$type}_{$phone}";

        // Cache-এ 2 মিনিটের জন্য store
        Cache::put($key, $otp, now()->addMinutes(2));

        // SMS send (production-এ real SMS gateway)
        // Development-এ log করো
        Log::info("OTP for {$phone}: {$otp}");

        // TODO: SMS Gateway integration
        // SmsService::send($phone, "Your ReadyRide OTP: {$otp}");

        return true;
    }

    // OTP verify করা
    public function verify(string $phone, string $otp, string $type = 'user'): bool
    {
        $key = "otp_{$type}_{$phone}";
        $stored = Cache::get($key);

        if ($stored && $stored == $otp) {
            Cache::forget($key);
            return true;
        }
        return false;
    }

    // Resend (30 সেকেন্ড cooldown)
    public function canResend(string $phone, string $type = 'user'): bool
    {
        return !Cache::has("otp_cooldown_{$type}_{$phone}");
    }

    public function setCooldown(string $phone, string $type = 'user'): void
    {
        Cache::put("otp_cooldown_{$type}_{$phone}", true, now()->addSeconds(30));
    }
}
```

---

## ২. Customer Auth APIs

### POST /api/v1/auth/send-otp
```
Description: Phone number-এ OTP পাঠানো

Request Body:
{
    "phone": "01712345678"  // required, bangladeshi format
}

Response (200):
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "phone": "01712345678",
        "resend_after": 30  // seconds
    }
}

Response (429 — Too Many Requests):
{
    "success": false,
    "message": "Please wait 30 seconds before requesting another OTP"
}

Logic:
- Phone format validate: /^01[3-9]\d{8}$/
- OtpService::send($phone, 'user')
- New user হলে auto-register করো না এখনই
```

### POST /api/v1/auth/verify-otp
```
Description: OTP verify করে login/register

Request Body:
{
    "phone": "01712345678",
    "otp": "123456",
    "fcm_token": "device_fcm_token",  // optional
    "name": "John Doe"  // required only for new users
}

Response (200 — Existing User):
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "sanctum_token_here",
        "token_type": "Bearer",
        "is_new_user": false,
        "user": {
            "id": 1,
            "name": "John Doe",
            "phone": "01712345678",
            "email": null,
            "avatar": null,
            "wallet_balance": "150.00",
            "referral_code": "JOHN123",
            "is_active": true
        }
    }
}

Response (200 — New User, name not provided):
{
    "success": true,
    "message": "OTP verified",
    "data": {
        "is_new_user": true,
        "phone": "01712345678",
        "temp_token": "temporary_token"  // registration complete করতে লাগবে
    }
}

Response (422 — Wrong OTP):
{
    "success": false,
    "message": "Invalid or expired OTP"
}

Logic:
- OtpService::verify($phone, $otp, 'user')
- User exists? → update fcm_token, return sanctum token
- User not exists?
    - name provided? → create user, return token
    - name not provided? → return temp_token (next step: complete profile)
- Referral code generate: strtoupper(substr(name, 0, 4)) + random(1000,9999)
```

### POST /api/v1/auth/complete-profile
```
Description: নতুন user-এর profile complete করা

Headers:
Authorization: Bearer {temp_token}

Request Body:
{
    "name": "John Doe",            // required
    "email": "john@example.com",   // optional
    "referral_code": "ABCD1234"    // optional — অন্যের code দিলে bonus
}

Response (200):
{
    "success": true,
    "message": "Profile created successfully",
    "data": {
        "token": "sanctum_token_here",
        "token_type": "Bearer",
        "user": { ...user object... }
    }
}

Logic:
- Referral code valid হলে referrals table-এ insert
- Wallet bonus (যদি referral_enabled): WalletService::creditUser()
- Real token generate করো, temp_token revoke
```

### POST /api/v1/auth/logout
```
Headers: Authorization: Bearer {token}

Response (200):
{
    "success": true,
    "message": "Logged out successfully"
}

Logic: $request->user()->currentAccessToken()->delete()
```

### POST /api/v1/auth/refresh-token
```
Description: Token refresh (optional — Sanctum token long-lived তাই rarely needed)

Headers: Authorization: Bearer {token}

Response (200):
{
    "success": true,
    "data": {
        "token": "new_token"
    }
}
```

---

## ৩. Customer Profile APIs

### GET /api/v1/user/profile
```
Headers: Authorization: Bearer {token}
Middleware: auth:sanctum + AuthenticateUser

Response (200):
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "phone": "01712345678",
        "email": "john@example.com",
        "avatar": "https://domain.com/storage/avatars/user_1.jpg",
        "wallet_balance": "150.00",
        "referral_code": "JOHN1234",
        "referred_by": null,
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

### PUT /api/v1/user/profile
```
Headers: Authorization: Bearer {token}
Content-Type: multipart/form-data

Request Body:
{
    "name": "John Doe Updated",
    "email": "newemail@example.com",
    "avatar": [file]  // optional image
}

Response (200):
{
    "success": true,
    "message": "Profile updated successfully",
    "data": { ...updated user object... }
}

Validation:
- name: sometimes, min:2, max:100
- email: sometimes, email, unique:users,email,{user_id}
- avatar: sometimes, image, max:2048, mimes:jpg,jpeg,png
```

### POST /api/v1/user/update-fcm-token
```
Request Body:
{
    "fcm_token": "new_device_token"
}

Response (200):
{
    "success": true,
    "message": "FCM token updated"
}
```

---

## ৪. Driver Auth APIs

### POST /api/v1/driver/auth/send-otp
```
Request Body:
{
    "phone": "01712345678"
}

Same logic as customer OTP but type = 'driver'
```

### POST /api/v1/driver/auth/verify-otp
```
Request Body:
{
    "phone": "01712345678",
    "otp": "123456",
    "fcm_token": "device_token"
}

Response (200 — Existing Approved Driver):
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "sanctum_token",
        "token_type": "Bearer",
        "driver": {
            "id": 1,
            "name": "Driver Name",
            "phone": "01712345678",
            "avatar": null,
            "status": "approved",
            "is_online": false,
            "wallet_balance": "500.00",
            "due_amount": "0.00",
            "average_rating": "4.80",
            "total_trips": 150,
            "zone": {
                "id": 1,
                "name": "Dhaka North"
            },
            "vehicle": {
                "id": 1,
                "category": "Bike",
                "make": "Honda",
                "model": "CB150R",
                "registration_number": "DHA-1234"
            }
        }
    }
}

Response (200 — New Driver / Pending):
{
    "success": true,
    "message": "OTP verified",
    "data": {
        "is_new_driver": true,
        "status": "pending",
        "token": "sanctum_token",  // limited access token
        "driver": { "id": X, "status": "pending" }
    }
}

Response (403 — Blocked):
{
    "success": false,
    "message": "Your account has been blocked.",
    "status": "blocked"
}
```

### POST /api/v1/driver/auth/register
```
Description: নতুন Driver registration (Onboarding step 1)
Headers: Authorization: Bearer {temp_token}

Request Body (multipart/form-data):
{
    "name": "Driver Name",
    "email": "driver@example.com",  // optional
    // Documents
    "nid_front": [file],
    "nid_back": [file],
    "license_front": [file],
    "license_expiry": "2026-12-31",
    "vehicle_registration_doc": [file],
    "vehicle_registration_expiry": "2026-06-30",
    "insurance_doc": [file],
    "insurance_expiry": "2025-12-31",
    "vehicle_front_photo": [file],
    "vehicle_back_photo": [file],
    // Vehicle info
    "vehicle_category_id": 1,
    "vehicle_make": "Honda",
    "vehicle_model": "CB150R",
    "vehicle_year": "2022",
    "vehicle_color": "Red",
    "vehicle_registration_number": "DHA-1234",
    // Bank/Wallet
    "withdrawal_method": "bkash",  // bank/bkash/nagad
    "withdrawal_account": "01712345678"
}

Response (200):
{
    "success": true,
    "message": "Registration submitted. Awaiting admin approval.",
    "data": {
        "driver": {
            "id": X,
            "status": "pending"
        }
    }
}
```

### GET /api/v1/driver/auth/status
```
Description: Driver approval status check
Headers: Authorization: Bearer {token}

Response:
{
    "success": true,
    "data": {
        "status": "pending",  // pending/approved/rejected
        "rejection_reason": null,
        "documents": [
            {
                "type": "driving_license",
                "status": "approved",
                "expiry_date": "2026-12-31"
            },
            ...
        ]
    }
}
```

### POST /api/v1/driver/auth/logout
```
Same as customer logout
```

---

## ৫. Driver Profile APIs

### GET /api/v1/driver/profile
```
Response: Driver object with vehicle, zone, documents status
```

### PUT /api/v1/driver/profile
```
Editable: name, email, avatar, withdrawal info
```

### POST /api/v1/driver/update-fcm-token
```
Same as customer
```

### POST /api/v1/driver/documents/update
```
Description: Expired/Rejected document re-upload
Multipart form data
document_type + file(s) + expiry_date
```

---

## ৬. Routes (api.php)

```php
// routes/api.php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Customer Auth (Public) ──────────────────────
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('send-otp', [CustomerAuthController::class, 'sendOtp']);
        Route::post('verify-otp', [CustomerAuthController::class, 'verifyOtp']);
        Route::post('complete-profile', [CustomerAuthController::class, 'completeProfile'])
             ->middleware('auth:sanctum');
        Route::post('logout', [CustomerAuthController::class, 'logout'])
             ->middleware('auth:sanctum');
        Route::post('refresh-token', [CustomerAuthController::class, 'refreshToken'])
             ->middleware('auth:sanctum');
    });

    // ── Customer Protected ──────────────────────────
    Route::prefix('user')->middleware(['auth:sanctum', 'auth.user'])->group(function () {
        Route::get('profile', [CustomerProfileController::class, 'show']);
        Route::put('profile', [CustomerProfileController::class, 'update']);
        Route::post('update-fcm-token', [CustomerProfileController::class, 'updateFcmToken']);
    });

    // ── Driver Auth (Public) ────────────────────────
    Route::prefix('driver/auth')->group(function () {
        Route::post('send-otp', [DriverAuthController::class, 'sendOtp']);
        Route::post('verify-otp', [DriverAuthController::class, 'verifyOtp']);
        Route::post('register', [DriverAuthController::class, 'register'])
             ->middleware('auth:sanctum');
        Route::get('status', [DriverAuthController::class, 'status'])
             ->middleware('auth:sanctum');
        Route::post('logout', [DriverAuthController::class, 'logout'])
             ->middleware('auth:sanctum');
    });

    // ── Driver Protected ────────────────────────────
    Route::prefix('driver')->middleware(['auth:sanctum', 'auth.driver'])->group(function () {
        Route::get('profile', [DriverProfileController::class, 'show']);
        Route::put('profile', [DriverProfileController::class, 'update']);
        Route::post('update-fcm-token', [DriverProfileController::class, 'updateFcmToken']);
        Route::post('documents/update', [DriverProfileController::class, 'updateDocument']);
    });

});
```

---

## ৭. File Structure

```
app/Http/Controllers/Api/
  Auth/
    CustomerAuthController.php
    DriverAuthController.php
  Customer/
    CustomerProfileController.php
  Driver/
    DriverProfileController.php

app/Http/Middleware/
  AuthenticateUser.php
  AuthenticateDriver.php

app/Services/
  OtpService.php
  (WalletService.php — Phase 7 থেকে already exists)

app/Traits/
  ApiResponse.php

app/Http/Resources/
  UserResource.php
  DriverResource.php
```

---

## ৮. API Resources (JSON transform)

```php
// app/Http/Resources/UserResource.php
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'avatar'         => $this->avatar
                                    ? Storage::url($this->avatar)
                                    : null,
            'wallet_balance' => number_format($this->wallet_balance, 2, '.', ''),
            'referral_code'  => $this->referral_code,
            'is_active'      => $this->is_active,
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}

// app/Http/Resources/DriverResource.php
class DriverResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'phone'           => $this->phone,
            'email'           => $this->email,
            'avatar'          => $this->avatar ? Storage::url($this->avatar) : null,
            'status'          => $this->status,
            'is_online'       => $this->is_online,
            'wallet_balance'  => number_format($this->wallet_balance, 2, '.', ''),
            'due_amount'      => number_format($this->due_amount, 2, '.', ''),
            'average_rating'  => $this->average_rating,
            'total_trips'     => $this->total_trips,
            'zone'            => $this->zone ? [
                'id'   => $this->zone->id,
                'name' => $this->zone->name,
            ] : null,
            'vehicle'         => $this->activeVehicle ? [
                'id'                  => $this->activeVehicle->id,
                'category'            => $this->activeVehicle->vehicleCategory->name,
                'category_id'         => $this->activeVehicle->vehicle_category_id,
                'make'                => $this->activeVehicle->make,
                'model'               => $this->activeVehicle->model,
                'registration_number' => $this->activeVehicle->registration_number,
            ] : null,
        ];
    }
}
```

---

## ৯. Kernel.php — Middleware Register

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    // existing...
    'auth.user'   => AuthenticateUser::class,
    'auth.driver' => AuthenticateDriver::class,
];
```

---

## গুরুত্বপূর্ণ নিয়ম

1. সব API response **ApiResponse trait** দিয়ে return করবে
2. Validation error → `error()` with `$errors = $validator->errors()`
3. OTP development-এ **Log** করবে — production-এ SMS gateway
4. File upload → `storage/app/public/` এ store, `Storage::url()` দিয়ে serve
5. Sanctum token **abilities** use করবে:
   - Normal user: `['user']`
   - Temp/pending user: `['temp']`
   - Driver: `['driver']`
6. CORS configure করবে (`config/cors.php`) — Flutter app থেকে request আসবে

---

## শুরু করো এই order-এ

1. Sanctum install + configure
2. `ApiResponse` trait
3. `OtpService`
4. Middleware (`AuthenticateUser`, `AuthenticateDriver`)
5. Resources (`UserResource`, `DriverResource`)
6. `CustomerAuthController`
7. `DriverAuthController`
8. Profile controllers
9. Routes (api.php)
10. CORS configure
11. Test: Postman/Insomnia দিয়ে সব endpoints test করো
