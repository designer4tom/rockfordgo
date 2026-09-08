<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores each admin's own sidebar ordering. Purely a display preference —
 * it never widens what an admin can reach, because the sidebar is still
 * rendered from permission-gated markup and every route keeps its own guard.
 */
class NavOrderController extends Controller
{
    /** Maximum keys we will store, as a guard against a runaway payload. */
    private const MAX_SECTIONS = 20;

    private const MAX_ITEMS = 40;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'max:' . self::MAX_SECTIONS],
            'order.*' => ['array', 'max:' . self::MAX_ITEMS],
            'order.*.*' => ['string', 'max:60'],
        ]);

        $admin = auth('admin')->user();
        $admin->forceFill(['nav_order' => $data['order']])->save();

        return response()->json(['ok' => true]);
    }

    public function reset(): JsonResponse
    {
        $admin = auth('admin')->user();
        $admin->forceFill(['nav_order' => null])->save();

        return response()->json(['ok' => true]);
    }

    /** Which columns this admin hides on a given table. */
    public function columns(Request $request): JsonResponse
    {
        $data = $request->validate([
            'table' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_-]+$/'],
            'hidden' => ['present', 'array', 'max:' . self::MAX_ITEMS],
            'hidden.*' => ['string', 'max:40'],
        ]);

        $admin = auth('admin')->user();
        $prefs = $admin->table_prefs ?? [];
        $prefs[$data['table']] = array_values(array_unique($data['hidden']));

        $admin->forceFill(['table_prefs' => $prefs])->save();

        return response()->json(['ok' => true]);
    }
}
