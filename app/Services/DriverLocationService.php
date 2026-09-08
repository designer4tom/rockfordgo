<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * Live driver locations in a Redis GEO set for fast "nearest drivers" lookups
 * during dispatch (GEOADD / GEOSEARCH).
 *
 * Degrades gracefully: if no Redis client is installed or the server is
 * unreachable, every method is a safe no-op and nearby() returns null so the
 * caller falls back to the SQL Haversine query. This lets the app run on the
 * current database stack and switch to Redis GEO automatically once Redis is
 * available — no code change required.
 */
class DriverLocationService
{
    /** GEO set holding every currently-online driver. */
    private const KEY = 'drivers:geo';

    /** Memoised availability so we don't ping/connect on every call. */
    private ?bool $available = null;

    private function available(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        // No client at all → never attempt (avoids throwing on every call).
        if (! extension_loaded('redis') && ! class_exists(\Predis\Client::class)) {
            return $this->available = false;
        }

        try {
            Redis::connection()->ping();

            return $this->available = true;
        } catch (\Throwable $e) {
            return $this->available = false;
        }
    }

    /** Add / update a driver's position in the GEO set. */
    public function add(int $driverId, ?float $lat, ?float $lng): void
    {
        if ($lat === null || $lng === null || ! $this->available()) {
            return;
        }

        try {
            Redis::connection()->geoadd(self::KEY, $lng, $lat, (string) $driverId);
        } catch (\Throwable $e) {
            $this->available = false;
        }
    }

    /** Remove a driver (e.g. when they go offline). */
    public function remove(int $driverId): void
    {
        if (! $this->available()) {
            return;
        }

        try {
            Redis::connection()->zrem(self::KEY, (string) $driverId);
        } catch (\Throwable $e) {
            $this->available = false;
        }
    }

    /**
     * Nearest-first driver IDs within $radiusKm of the point.
     * Returns null when Redis GEO is unavailable (signals "fall back to SQL").
     */
    public function nearby(float $lat, float $lng, float $radiusKm): ?array
    {
        if (! $this->available()) {
            return null;
        }

        try {
            // phpredis: geosearch(key, [lng,lat], radius, unit, options)
            $members = Redis::connection()->geosearch(
                self::KEY,
                [$lng, $lat],
                $radiusKm,
                'km',
                ['ASC']
            );

            return array_values(array_map('intval', (array) $members));
        } catch (\Throwable $e) {
            $this->available = false;

            return null;
        }
    }
}
