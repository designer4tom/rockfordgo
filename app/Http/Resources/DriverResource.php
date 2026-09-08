<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DriverResource extends JsonResource
{
    public function toArray($request): array
    {
        $vehicle = $this->activeVehicle ?? $this->vehicles->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'status' => $this->status,
            'is_online' => (bool) $this->is_online,
            'wallet_balance' => number_format((float) $this->wallet_balance, 2, '.', ''),
            'due_amount' => number_format((float) $this->due_amount, 2, '.', ''),
            'average_rating' => (float) $this->average_rating,
            'total_trips' => (int) $this->total_trips,
            // Auto-detected from the driver's location — read-only (driver can't change it).
            'current_zone' => $this->zone ? [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
            ] : null,
            // Kept for backward compatibility with existing app builds.
            'zone' => $this->zone ? [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
            ] : null,
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'category' => $vehicle->vehicleCategory?->name,
                'category_id' => $vehicle->vehicle_category_id,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'registration_number' => $vehicle->registration_number,
            ] : null,
            // Uploaded documents (only when eager-loaded, e.g. GET /driver/profile).
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'id' => $d->id,
                'type' => $d->type,
                'status' => $d->status,
                'front_image' => $d->front_image ? Storage::url($d->front_image) : null,
                'back_image' => $d->back_image ? Storage::url($d->back_image) : null,
                'expiry_date' => $d->expiry_date ? \Illuminate\Support\Carbon::parse($d->expiry_date)->toDateString() : null,
                'rejection_reason' => $d->rejection_reason,
            ])->values()),
        ];
    }
}
