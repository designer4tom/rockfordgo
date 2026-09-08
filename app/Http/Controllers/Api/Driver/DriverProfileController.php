<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\DriverDocument;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DriverProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request)
    {
        $driver = $request->user()->load(['zone', 'activeVehicle.vehicleCategory', 'documents']);

        return $this->success(new DriverResource($driver), 'Profile fetched.');
    }

    public function update(Request $request)
    {
        $driver = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('drivers', 'email')->ignore($driver->id)],
            'avatar' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'withdrawal_method' => ['sometimes', 'nullable', 'in:bkash,nagad,bank'],
            'withdrawal_account' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($driver->avatar) {
                Storage::disk('public')->delete($driver->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('drivers', 'public');
        }

        $driver->update($data);

        return $this->success(new DriverResource($driver->fresh()->load(['zone', 'activeVehicle.vehicleCategory'])), 'Profile updated successfully.');
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

        $driver = $request->user();
        \App\Models\DeviceToken::register('driver', $driver->id, $token, $request->input('platform'));
        $driver->update(['fcm_token' => $token]);

        return $this->success(null, 'FCM token updated.');
    }

    // Re-upload an expired / rejected document.
    public function updateDocument(Request $request)
    {
        $driver = $request->user();

        $data = $request->validate([
            'document_type' => ['required', 'in:nid,driving_license,vehicle_registration,vehicle_insurance,vehicle_photo,bank_info'],
            'front_image' => ['required', 'image', 'max:4096'],
            'back_image' => ['nullable', 'image', 'max:4096'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $front = $request->file('front_image')->store('drivers/documents', 'public');
        $back = $request->hasFile('back_image') ? $request->file('back_image')->store('drivers/documents', 'public') : null;

        // Reset the document back to pending for re-review.
        DriverDocument::updateOrCreate(
            ['driver_id' => $driver->id, 'type' => $data['document_type']],
            [
                'front_image' => $front,
                'back_image' => $back,
                'expiry_date' => $data['expiry_date'] ?? null,
                'status' => 'pending',
                'rejection_reason' => null,
                'verified_at' => null,
                'verified_by' => null,
            ]
        );

        return $this->success(null, 'Document submitted for review.');
    }

    // App Store / Play Store requirement: in-app account deletion (checks dues).
    public function deleteAccount(Request $request)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $driver = $request->user();

        $hasActive = $driver->orders()
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected', 'no_driver_found'])
            ->exists();
        if ($hasActive) {
            return $this->error('You have an ongoing trip. Please complete it before deleting your account.', 422);
        }
        if ((float) $driver->due_amount > 0) {
            return $this->error('Please clear your outstanding due before deleting your account.', 422);
        }

        $driver->update(['is_active' => false, 'is_online' => false, 'deletion_requested_at' => now()]);
        $driver->tokens()->delete();

        return $this->success(null, 'Account deletion requested. Your account will be deactivated.');
    }
}
