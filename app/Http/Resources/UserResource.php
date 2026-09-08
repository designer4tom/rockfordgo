<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar' => $this->avatar ? asset(Storage::url($this->avatar)) : null,
            'wallet_balance' => number_format((float) $this->wallet_balance, 2, '.', ''),
            'withdrawal_method' => $this->withdrawal_method,
            'withdrawal_account' => $this->withdrawal_account,
            'referral_code' => $this->referral_code,
            'referred_by' => $this->referred_by,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
