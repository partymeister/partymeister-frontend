<?php

namespace Partymeister\Frontend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ensures the authenticated user is a Visitor (not an admin User).
 * Applied after auth:sanctum to guard V2 visitor endpoints.
 */
class EnsureVisitorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $visitor = $request->user('visitor');

        if (is_null($visitor)) {
            return response()->json([
                'status'  => 401,
                'message' => 'Visitor authentication required',
            ], 401);
        }

        return $next($request);
    }
}
