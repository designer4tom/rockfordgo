<?php

namespace App\Services;

use App\Models\ParcelPricing;
use App\Models\SurgePricingRule;
use App\Models\VehicleCategory;
use App\Models\Zone;
use Illuminate\Support\Carbon;

class PricingService
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    /**
     * Calculate a ride fare breakdown.
     *
     * @return array{base: float, distance: float, time: float, subtotal: float, surge_multiplier: float, surge_amount: float, total: float}
     */
    public function calculateRideFare(
        VehicleCategory $category,
        float $distanceKm,
        int $durationMinutes,
        ?Zone $zone = null
    ): array {
        $base = (float) $category->base_fare;
        $distance = $distanceKm * (float) $category->per_km_rate;
        $time = $durationMinutes * (float) $category->per_minute_rate;
        $subtotal = $base + $distance + $time;

        $multiplier = $this->getActiveSurgeMultiplier($category->id, $zone?->id);
        $surgeAmount = round($subtotal * ($multiplier - 1), 2);

        $total = max($subtotal + $surgeAmount, (float) $category->minimum_fare);

        return [
            'base' => round($base, 2),
            'distance' => round($distance, 2),
            'time' => round($time, 2),
            'subtotal' => round($subtotal, 2),
            'surge_multiplier' => $multiplier,
            'surge_amount' => $surgeAmount,
            'total' => round($total, 2),
        ];
    }

    /**
     * Return the highest applicable surge multiplier (1.0 when none active).
     */
    public function getActiveSurgeMultiplier(int $vehicleCategoryId, ?int $zoneId = null): float
    {
        // Master switch must be on.
        if (! $this->settings->getBool('surge_enabled', false)) {
            return 1.0;
        }

        $now = Carbon::now();
        $time = $now->format('H:i:s');
        $weekday = $now->dayOfWeek; // 0 (Sun) .. 6 (Sat)

        $rules = SurgePricingRule::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('zone_id')->orWhere('zone_id', $zoneId))
            ->where(fn ($q) => $q->whereNull('vehicle_category_id')->orWhere('vehicle_category_id', $vehicleCategoryId))
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->where(fn ($q) => $q->where('is_manual', false)->orWhere('ends_at', '>', $now))
            ->get();

        $multiplier = 1.0;

        foreach ($rules as $rule) {
            // Day-of-week filter (null/empty = every day).
            $days = $rule->day_of_week;
            if (! empty($days) && ! in_array($weekday, array_map('intval', $days), true)) {
                continue;
            }

            $multiplier = max($multiplier, (float) $rule->multiplier);
        }

        return $multiplier;
    }

    /**
     * Calculate parcel delivery charge for a given type/weight/distance.
     */
    public function calculateParcelCharge(string $parcelType, float $weightKg, float $distanceKm): float
    {
        $pricing = ParcelPricing::query()
            ->where('is_active', true)
            ->where('parcel_type', $parcelType)
            ->where('min_weight', '<=', $weightKg)
            ->where('max_weight', '>=', $weightKg)
            ->orderBy('base_charge')
            ->first();

        if (! $pricing) {
            return 0.0;
        }

        return round((float) $pricing->base_charge + ($distanceKm * (float) $pricing->per_km_charge), 2);
    }

    /**
     * Split a total amount into admin commission and driver earning.
     *
     * @return array{admin_amount: float, driver_amount: float, percent: float}
     */
    public function splitCommission(float $totalAmount, string $type): array
    {
        $key = $type === 'parcel' ? 'parcel_admin_commission_percent' : 'ride_admin_commission_percent';
        $default = $type === 'parcel' ? '20' : '15';

        $percent = (float) $this->settings->get($key, $default);
        $adminAmount = round($totalAmount * $percent / 100, 2);
        $driverAmount = round($totalAmount - $adminAmount, 2);

        return [
            'admin_amount' => $adminAmount,
            'driver_amount' => $driverAmount,
            'percent' => $percent,
        ];
    }
}
