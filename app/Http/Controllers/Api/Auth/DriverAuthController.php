<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverVehicle;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverAuthController extends Controller
{
    use ApiResponse;

    // Phone validation comes from the admin-configured regex (Settings → General),
    // so each client can set their own country's pattern. See phoneValidationRules().

    public function __construct(private OtpService $otp)
    {
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => phoneValidationRules()]);
        $phone = $request->input('phone');

        if (! $this->otp->canResend($phone, 'driver')) {
            return $this->error('Please wait 30 seconds before requesting another OTP.', 429);
        }

        $otp = $this->otp->send($phone, 'driver');

        $data = ['phone' => $phone, 'resend_after' => 30];
        if (config('app.demo_mode')) {
            $data['otp'] = $otp; // demo/testing only
        }

        return $this->success($data, 'OTP sent successfully.');
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => phoneValidationRules(),
            'otp' => ['required', 'digits:6'],
            'fcm_token' => ['nullable', 'string'],
            'device_token' => ['nullable', 'string'],
            'platform' => ['nullable', 'in:android,ios,web'],
        ]);

        // Accept either field name from the apps.
        $data['fcm_token'] = $data['fcm_token'] ?? ($data['device_token'] ?? null);

        if (! $this->otp->verify($data['phone'], $data['otp'], 'driver')) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        $driver = Driver::where('phone', $data['phone'])->first();

        // ── Existing driver ──────────────────────────────────────────
        if ($driver) {
            if ($driver->status === 'blocked') {
                return $this->error('Your account has been blocked.', 403, ['status' => 'blocked']);
            }
            if (! empty($data['fcm_token'])) {
                $driver->update(['fcm_token' => $data['fcm_token']]);
            }
            $this->storeDeviceToken($driver, $request);

            $driver->load(['zone', 'activeVehicle.vehicleCategory', 'documents', 'vehicles']);
            $token = $driver->createToken('mobile', ['driver'])->plainTextToken;
            $completed = $driver->registration_completed;

            // Rejected — let the app show the reason and allow re-submission.
            if ($driver->status === 'rejected') {
                return $this->success([
                    'is_new_driver' => false,
                    'registration_completed' => $completed,
                    'status' => 'rejected',
                    'rejection_reason' => $driver->rejection_reason,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ], 'OTP verified.');
            }

            // Approved — full login.
            if ($driver->status === 'approved') {
                return $this->success([
                    'is_new_driver' => false,
                    'registration_completed' => true,
                    'status' => 'approved',
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'driver' => new DriverResource($driver),
                ], 'Login successful.');
            }

            // Pending (or suspended) — app routes to onboarding or "awaiting approval".
            return $this->success([
                'is_new_driver' => false,
                'registration_completed' => $completed,
                'status' => $driver->status,
                'token' => $token,
                'token_type' => 'Bearer',
                'driver' => ['id' => $driver->id, 'status' => $driver->status],
            ], 'OTP verified.');
        }

        // ── New driver → always created as pending, registration incomplete ──
        $driver = Driver::create([
            'phone' => $data['phone'],
            'name' => 'New Driver',
            'status' => 'pending',
            'fcm_token' => $data['fcm_token'] ?? null,
            'is_active' => true,
        ]);
        $this->storeDeviceToken($driver, $request);

        return $this->success([
            'is_new_driver' => true,
            'registration_completed' => false,
            'status' => 'pending',
            'token' => $driver->createToken('onboarding', ['driver'])->plainTextToken,
            'token_type' => 'Bearer',
            'driver' => ['id' => $driver->id, 'status' => 'pending'],
        ], 'OTP verified.');
    }

    // Persist the device's FCM token (a driver can have multiple devices).
    // Accepts either "fcm_token" or "device_token" from the apps.
    private function storeDeviceToken(Driver $driver, Request $request): void
    {
        $token = $request->input('fcm_token') ?: $request->input('device_token');
        if ($token) {
            \App\Models\DeviceToken::register('driver', $driver->id, $token, $request->input('platform'));
        }
    }

    public function register(Request $request)
    {
        $driver = $request->user();
        if (! $driver instanceof Driver) {
            return $this->error('Unauthorized', 401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'email'],
            'nid_front' => ['required', 'image', 'max:4096'],
            'nid_back' => ['required', 'image', 'max:4096'],
            'license_front' => ['required', 'image', 'max:4096'],
            'license_expiry' => ['required', 'date', 'after:today'],
            'vehicle_registration_doc' => ['required', 'image', 'max:4096'],
            'vehicle_registration_expiry' => ['required', 'date', 'after:today'],
            'insurance_doc' => ['nullable', 'image', 'max:4096'],
            'insurance_expiry' => ['nullable', 'date', 'after:today'],
            'vehicle_front_photo' => ['required', 'image', 'max:4096'],
            'vehicle_back_photo' => ['required', 'image', 'max:4096'],
            'vehicle_category_id' => ['required', 'exists:vehicle_categories,id'],
            'vehicle_make' => ['required', 'string', 'max:50'],
            'vehicle_model' => ['required', 'string', 'max:50'],
            'vehicle_year' => ['nullable', 'string', 'max:4'],
            'vehicle_color' => ['nullable', 'string', 'max:30'],
            'vehicle_registration_number' => ['required', 'string', 'max:30'],
        ]);

        // Store under a per-driver folder.
        $store = fn ($field) => $request->file($field)->store('drivers/' . $driver->id, 'public');

        DB::transaction(function () use ($driver, $data, $request, $store) {
            $driver->update([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'status' => 'pending',
            ]);

            // Documents.
            DriverDocument::create(['driver_id' => $driver->id, 'type' => 'nid', 'front_image' => $store('nid_front'), 'back_image' => $store('nid_back')]);
            DriverDocument::create(['driver_id' => $driver->id, 'type' => 'driving_license', 'front_image' => $store('license_front'), 'expiry_date' => $data['license_expiry']]);
            DriverDocument::create(['driver_id' => $driver->id, 'type' => 'vehicle_registration', 'front_image' => $store('vehicle_registration_doc'), 'expiry_date' => $data['vehicle_registration_expiry']]);
            if ($request->hasFile('insurance_doc')) {
                DriverDocument::create(['driver_id' => $driver->id, 'type' => 'vehicle_insurance', 'front_image' => $store('insurance_doc'), 'expiry_date' => $data['insurance_expiry'] ?? null]);
            }

            $vehicleFront = $store('vehicle_front_photo');
            $vehicleBack = $store('vehicle_back_photo');
            DriverDocument::create(['driver_id' => $driver->id, 'type' => 'vehicle_photo', 'front_image' => $vehicleFront, 'back_image' => $vehicleBack]);

            DriverVehicle::create([
                'driver_id' => $driver->id,
                'vehicle_category_id' => $data['vehicle_category_id'],
                'make' => $data['vehicle_make'],
                'model' => $data['vehicle_model'],
                'year' => $data['vehicle_year'] ?? '',
                'color' => $data['vehicle_color'] ?? '',
                'registration_number' => $data['vehicle_registration_number'],
                'front_photo' => $vehicleFront,
                'back_photo' => $vehicleBack,
                'is_active' => true,
            ]);
        });

        return $this->success([
            'status' => 'pending',
            'registration_completed' => true,
            'driver' => ['id' => $driver->id, 'status' => 'pending'],
        ], 'Registration submitted. Awaiting admin approval.');
    }

    public function status(Request $request)
    {
        $driver = $request->user()->load('documents', 'vehicles');

        return $this->success([
            'status' => $driver->status,
            'registration_completed' => $driver->registration_completed,
            'rejection_reason' => $driver->rejection_reason,
            'documents' => $driver->documents->map(fn ($d) => [
                'type' => $d->type,
                'status' => $d->status,
                'expiry_date' => $d->expiry_date?->toDateString(),
            ]),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }
}
