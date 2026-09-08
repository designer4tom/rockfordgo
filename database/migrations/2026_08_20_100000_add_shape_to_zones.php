<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records how a zone was defined.
 *
 * A pin-and-radius zone stores a generated 36-point polygon so that dispatch
 * can match it exactly, which makes it indistinguishable from a hand-drawn area
 * once saved. Reopening one for editing then showed a 36-sided blob instead of
 * the pin the admin actually placed. Storing the mode removes the guesswork.
 *
 * Nullable: existing rows are inferred once, below.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->string('shape', 10)->nullable()->after('polygon');
        });

        // Backfill: the pin editor always emits exactly 36 points, so a zone
        // with 36 points and a radius came from a pin. Everything else is
        // treated as drawn, which is the safe default — it shows the real
        // geometry rather than collapsing it to a circle.
        foreach (DB::table('zones')->get() as $zone) {
            $points = json_decode((string) $zone->polygon, true);
            $isPin = is_array($points) && count($points) === 36 && (float) $zone->radius_km > 0;

            DB::table('zones')->where('id', $zone->id)
                ->update(['shape' => $isPin ? 'pin' : 'draw']);
        }
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('shape');
        });
    }
};
