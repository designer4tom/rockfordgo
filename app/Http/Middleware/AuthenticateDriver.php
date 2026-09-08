<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Ensures the bearer token belongs to an approved Driver.
class AuthenticateDriver
{
    public function handle(Request $request, Closure $next): Response
    {
        $driver = $request->user();

        if (! $driver instanceof Driver) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($driver->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not approved yet.',
                'status' => $driver->status,
            ], 403);
        }

        return $next($request);
    }
}
