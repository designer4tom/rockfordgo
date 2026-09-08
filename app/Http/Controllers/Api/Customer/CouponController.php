<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CouponService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function __construct(private CouponService $coupons)
    {
    }

    public function validate(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'service_type' => ['required', 'in:ride,parcel'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->coupons->validate($data['code'], $data['service_type'], (float) $data['order_amount'], $request->user());

        if (! $result['ok']) {
            return $this->error($result['message'], 422);
        }

        $coupon = $result['coupon'];

        return $this->success([
            'valid' => true,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => number_format((float) $coupon->discount_value, 2, '.', ''),
            'discount_amount' => number_format($result['discount'], 2, '.', ''),
            'max_discount' => $coupon->max_discount ? number_format((float) $coupon->max_discount, 2, '.', '') : null,
            'final_amount' => number_format($result['final'], 2, '.', ''),
        ], 'Coupon is valid.');
    }

    public function index(Request $request)
    {
        $serviceType = $request->query('service_type');
        $userId = $request->user()->id;
        $today = now()->toDateString();

        $coupons = Coupon::where('is_active', true)
            ->whereDate('valid_from', '<=', $today)
            ->whereDate('valid_until', '>=', $today)
            ->when(in_array($serviceType, ['ride', 'parcel'], true), fn ($q) => $q->whereIn('service_type', [$serviceType, 'all']))
            ->get()
            // Hide coupons the user can no longer use (global or per-user limit reached).
            ->filter(function (Coupon $c) use ($userId) {
                if ($c->usage_limit && $c->used_count >= $c->usage_limit) {
                    return false;
                }
                return $c->usages()->where('user_id', $userId)->count() < $c->per_user_limit;
            })
            ->map(fn (Coupon $c) => [
                'code' => $c->code,
                'description' => $c->description,
                'discount_type' => $c->discount_type,
                'discount_value' => number_format((float) $c->discount_value, 2, '.', ''),
                'max_discount' => $c->max_discount ? number_format((float) $c->max_discount, 2, '.', '') : null,
                'min_order_amount' => number_format((float) $c->min_order_amount, 2, '.', ''),
                'valid_until' => $c->valid_until->toDateString(),
            ])
            ->values();

        return $this->success($coupons, 'Coupons fetched.');
    }
}
