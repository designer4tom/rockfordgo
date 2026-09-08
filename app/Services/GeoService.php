<?php

namespace App\Services;

/**
 * Lightweight geo helpers. Distance is computed with the Haversine formula and
 * duration estimated from an average city speed — no external API needed.
 * (A Google Distance Matrix call can be slotted in here later, cached 5 min.)
 */
class GeoService
{
    private const AVG_SPEED_KMH = 22;

    // Straight-line distance in kilometres, padded ~25% to approximate road distance.
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $straight = $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($straight * 1.25, 2);
    }

    // Distance across pickup → stops → drop.
    public function routeDistanceKm(array $points): float
    {
        $total = 0.0;
        for ($i = 0; $i < count($points) - 1; $i++) {
            $total += $this->distanceKm($points[$i][0], $points[$i][1], $points[$i + 1][0], $points[$i + 1][1]);
        }

        return round($total, 2);
    }

    public function estimateMinutes(float $distanceKm): int
    {
        return max(1, (int) ceil($distanceKm / self::AVG_SPEED_KMH * 60));
    }

    // Compass bearing between two points (degrees, for marker rotation).
    public function bearing(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $dLng = deg2rad($lng2 - $lng1);
        $y = sin($dLng) * cos(deg2rad($lat2));
        $x = cos(deg2rad($lat1)) * sin(deg2rad($lat2))
            - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos($dLng);

        return (int) round(fmod(rad2deg(atan2($y, $x)) + 360, 360));
    }
}
