<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;

/**
 * Validates a coupon for a given customer / service / order amount and computes
 * the discount. Used by ride & parcel booking and the standalone validate API.
 */
class CouponService
{
    /**
     * @return array{ok: bool, message?: string, coupon?: Coupon, discount?: float, final?: float}
     */
    public function validate(string $code, string $serviceType, float $amount, ?User $user = null): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (! $coupon || ! $coupon->is_active) {
            return ['ok' => false, 'message' => 'Invalid or expired coupon code.'];
        }

        $today = now()->toDateString();
        if ($coupon->valid_from->toDateString() > $today || $coupon->valid_until->toDateString() < $today) {
            return ['ok' => false, 'message' => 'This coupon is not valid right now.'];
        }

        if (! in_array($coupon->service_type, [$serviceType, 'all'], true)) {
            return ['ok' => false, 'message' => 'This coupon does not apply to this service.'];
        }

        if ($amount < (float) $coupon->min_order_amount) {
            return ['ok' => false, 'message' => 'Minimum order amount is ' . number_format($coupon->min_order_amount, 2) . '.'];
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return ['ok' => false, 'message' => 'This coupon has reached its usage limit.'];
        }

        if ($user) {
            $usedByUser = $coupon->usages()->where('user_id', $user->id)->count();
            if ($usedByUser >= $coupon->per_user_limit) {
                return ['ok' => false, 'message' => 'You have already used this coupon.'];
            }
        }

        $discount = $coupon->discount_type === 'percentage'
            ? $amount * (float) $coupon->discount_value / 100
            : (float) $coupon->discount_value;

        if ($coupon->max_discount) {
            $discount = min($discount, (float) $coupon->max_discount);
        }
        $discount = round(min($discount, $amount), 2);

        return [
            'ok' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'final' => round($amount - $discount, 2),
        ];
    }
}
