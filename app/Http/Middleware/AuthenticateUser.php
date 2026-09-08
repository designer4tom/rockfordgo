<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Ensures the bearer token belongs to a customer (User) and the account is active.
class AuthenticateUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'Your account has been blocked.'], 403);
        }

        return $next($request);
    }
}
