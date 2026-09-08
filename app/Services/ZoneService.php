<?php

namespace App\Services;

use App\Models\Zone;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a lat/lng to the zone that contains it. Zones are analytics-only —
 * drivers never pick a zone; we auto-detect it from their location.
 *
 * Detection: point-in-polygon (ray casting) against each active zone's polygon;
 * if no polygon matches (or a zone has none), fall back to the nearest zone
 * whose center is within its radius_km.
 */
class ZoneService
{
    /** Active zones cached for 5 min (plain arrays — cache-safe). */
    private function zones(): array
    {
        return Cache::remember('zones:active', 300, function () {
            return Zone::where('is_active', true)
                ->get(['id', 'polygon', 'center_lat', 'center_lng', 'radius_km'])
                ->map(fn ($z) => [
                    'id' => $z->id,
                    'polygon' => is_array($z->polygon) ? $z->polygon : [],
                    'center_lat' => (float) $z->center_lat,
                    'center_lng' => (float) $z->center_lng,
                    'radius_km' => (float) $z->radius_km,
                ])->all();
        });
    }

    /** Return the id of the zone containing the point, or null. */
    public function detectZone(?float $lat, ?float $lng): ?int
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $nearestId = null;
        $nearestDist = INF;

        foreach ($this->zones() as $zone) {
            // Exact match — point inside the drawn polygon.
            if (! empty($zone['polygon']) && $this->pointInPolygon($lat, $lng, $zone['polygon'])) {
                return $zone['id'];
            }

            // Fallback candidate — within the zone's circular radius.
            if ($zone['radius_km'] > 0) {
                $dist = $this->haversineKm($lat, $lng, $zone['center_lat'], $zone['center_lng']);
                if ($dist <= $zone['radius_km'] && $dist < $nearestDist) {
                    $nearestDist = $dist;
                    $nearestId = $zone['id'];
                }
            }
        }

        return $nearestId;
    }

    public function forgetCache(): void
    {
        Cache::forget('zones:active');
    }

    // Ray-casting: is (lat,lng) inside the polygon (array of {lat,lng} points)?
    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = (float) ($polygon[$i]['lat'] ?? $polygon[$i][0] ?? 0);
            $xi = (float) ($polygon[$i]['lng'] ?? $polygon[$i][1] ?? 0);
            $yj = (float) ($polygon[$j]['lat'] ?? $polygon[$j][0] ?? 0);
            $xj = (float) ($polygon[$j]['lng'] ?? $polygon[$j][1] ?? 0);

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
