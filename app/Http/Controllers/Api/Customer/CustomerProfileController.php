<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request)
    {
        return $this->success(new UserResource($request->user()), 'Profile fetched.');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'withdrawal_method' => ['sometimes', 'nullable', Rule::in(\App\Models\WithdrawalMethod::active()->pluck('code')->all())],
            'withdrawal_account' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return $this->success(new UserResource($user->fresh()), 'Profile updated successfully.');
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => ['nullable', 'string'],
            'device_token' => ['nullable', 'string'],
            'platform' => ['nullable', 'in:android,ios,web'],
        ]);

        $token = $request->input('fcm_token') ?: $request->input('device_token');
        if (! $token) {
            return $this->error('The fcm_token (or device_token) field is required.', 422);
        }

        $user = $request->user();
        // Store as a device token (a user can have several devices)…
        \App\Models\DeviceToken::register('user', $user->id, $token, $request->input('platform'));
        // …and keep the legacy single-token column in sync for backward compatibility.
        $user->update(['fcm_token' => $token]);

        return $this->success(null, 'FCM token updated.');
    }

    // App Store / Play Store requirement: in-app account deletion.
    public function deleteAccount(Request $request)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $user = $request->user();

        $hasActive = $user->orders()
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected', 'no_driver_found'])
            ->exists();
        if ($hasActive) {
            return $this->error('You have an ongoing order. Please complete it before deleting your account.', 422);
        }
        if ((float) $user->due_amount > 0) {
            return $this->error('Please clear your outstanding due before deleting your account.', 422);
        }

        $user->update(['is_active' => false, 'deletion_requested_at' => now()]);
        $user->tokens()->delete();

        return $this->success(null, 'Account deletion requested. Your account will be deactivated.');
    }
}
