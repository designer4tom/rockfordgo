<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for "is this app really installed?".
 *
 * The lock file (storage/installed) is only a fast cache — never proof. A real
 * install means: the database is reachable, migrated, AND has at least one admin.
 * This prevents the classic trap where a stray/mis-shipped `installed` file makes
 * the app skip the installer while the DB is empty (→ 500s, and no admin to log
 * in with). If the lock lies, we self-heal (delete a stale lock / ignore a bare one).
 */
class InstallState
{
    public static function lockPath(): string
    {
        return storage_path('installed');
    }

    public static function isInstalled(): bool
    {
        $lock = self::lockPath();

        try {
            if (is_file($lock)) {
                // Trust the lock ONLY if the DB genuinely has an admin (cheap,
                // indexed check). Otherwise the lock is stale/mis-placed.
                if (DB::table('admins')->exists()) {
                    return true;
                }

                // DB is reachable but there's no admin → the lock is a lie. Drop it
                // so the installer runs and the buyer can finish setup.
                @unlink($lock);

                return false;
            }

            // No lock → detect a real, already-finished install and cache it.
            if (Schema::hasTable('admins') && DB::table('admins')->exists()) {
                @file_put_contents($lock, now()->toDateTimeString());

                return true;
            }
        } catch (\Throwable) {
            // DB not configured / unreachable → NOT installed. A bare lock file is
            // never enough on its own (this is the fresh-deploy / broken drop-in case).
        }

        return false;
    }

    /** Mark the install complete (called by the installer after the admin is created). */
    public static function markInstalled(): void
    {
        @file_put_contents(self::lockPath(), now()->toDateTimeString());
    }
}
