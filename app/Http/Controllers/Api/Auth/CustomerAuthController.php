<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Referral;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SystemSettingService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerAuthController extends Controller
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

        if (! $this->otp->canResend($phone, 'user')) {
            return $this->error('Please wait 30 seconds before requesting another OTP.', 429);
        }

        $otp = $this->otp->send($phone, 'user');

        $data = [
            'phone' => $phone,
            'resend_after' => 30,
            'user_exists' => User::where('phone', $phone)->exists(),
        ];
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
            'name' => ['nullable', 'string', 'min:2', 'max:100'],
        ]);

        // Accept either field name from the apps.
        $data['fcm_token'] = $data['fcm_token'] ?? ($data['device_token'] ?? null);

        if (! $this->otp->verify($data['phone'], $data['otp'], 'user')) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        $user = User::where('phone', $data['phone'])->first();

        // Existing customer → log in.
        if ($user) {
            if (! $user->is_active) {
                return $this->error('Your account has been blocked.', 403);
            }
            if (! empty($data['fcm_token'])) {
                $user->update(['fcm_token' => $data['fcm_token']]);
            }
            $this->storeDeviceToken($user, $request);

            return $this->success([
                'token' => $user->createToken('mobile', ['user'])->plainTextToken,
                'token_type' => 'Bearer',
                'is_new_user' => false,
                'user' => new UserResource($user),
            ], 'Login successful.');
        }

        // New customer with a name → register immediately.
        if (! empty($data['name'])) {
            $user = $this->createUser($data['phone'], $data['name'], $data['fcm_token'] ?? null);
            $this->storeDeviceToken($user, $request);

            return $this->success([
                'token' => $user->createToken('mobile', ['user'])->plainTextToken,
                'token_type' => 'Bearer',
                'is_new_user' => true,
                'user' => new UserResource($user),
            ], 'Account created.');
        }

        // New customer without a name → issue a temp token to complete the profile.
        $user = $this->createUser($data['phone'], 'ReadyRide User', $data['fcm_token'] ?? null);
        $this->storeDeviceToken($user, $request);

        return $this->success([
            'is_new_user' => true,
            'phone' => $data['phone'],
            'temp_token' => $user->createToken('temp', ['temp'])->plainTextToken,
        ], 'OTP verified.');
    }

    // Persist the device's FCM token (a customer can have multiple devices).
    // Accepts either "fcm_token" or "device_token" from the apps.
    private function storeDeviceToken(User $user, Request $request): void
    {
        $token = $request->input('fcm_token') ?: $request->input('device_token');
        if ($token) {
            \App\Models\DeviceToken::register('user', $user->id, $token, $request->input('platform'));
        }
    }

    public function completeProfile(Request $request, SystemSettingService $settings, WalletService $wallet)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $update = ['name' => $data['name'], 'email' => $data['email'] ?? null];

        // Optional profile photo at registration.
        if ($request->hasFile('avatar')) {
            $update['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($update);

        // Apply referral (once, can't refer self).
        if (! empty($data['referral_code']) && ! $user->referred_by) {
            $referrer = User::where('referral_code', $data['referral_code'])->first();
            if ($referrer && $referrer->id !== $user->id) {
                $user->update(['referred_by' => $referrer->id]);
                $this->rewardReferral($referrer, $user, $settings, $wallet);
            }
        }

        // Swap the temp token for a full-access token.
        $user->tokens()->delete();

        return $this->success([
            'token' => $user->createToken('mobile', ['user'])->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->fresh()),
        ], 'Profile created successfully.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        return $this->success([
            'token' => $user->createToken('mobile', ['user'])->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Token refreshed.');
    }

    // ---------------------------------------------------------------------

    private function createUser(string $phone, string $name, ?string $fcmToken): User
    {
        return User::create([
            'phone' => $phone,
            'name' => $name,
            'fcm_token' => $fcmToken,
            'referral_code' => $this->uniqueReferralCode($name),
            'is_active' => true,
        ]);
    }

    private function uniqueReferralCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'USER', 0, 4));
        do {
            $code = $prefix . random_int(1000, 9999);
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    private function rewardReferral(User $referrer, User $referee, SystemSettingService $settings, WalletService $wallet): void
    {
        if (! $settings->getBool('referral_enabled', false)) {
            return;
        }

        $referrerBonus = (float) $settings->get('referral_referrer_bonus', 0);
        $refereeBonus = (float) $settings->get('referral_referee_bonus', 0);

        Referral::create([
            'referrer_id' => $referrer->id,
            'referee_id' => $referee->id,
            'referrer_bonus' => $referrerBonus,
            'referee_bonus' => $refereeBonus,
            'status' => 'rewarded',
            'rewarded_at' => now(),
        ]);

        if ($referrerBonus > 0) {
            $wallet->creditUser($referrer, $referrerBonus, 'referral_bonus', null, 'Referral bonus');
        }
        if ($refereeBonus > 0) {
            $wallet->creditUser($referee, $refereeBonus, 'referral_bonus', null, 'Welcome referral bonus');
        }
    }
}
