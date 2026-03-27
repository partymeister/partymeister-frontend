<?php

namespace Partymeister\Frontend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Partymeister\Core\Models\Visitor;

/**
 * Ensures the authenticated user is a Visitor (not an admin User).
 * Applied after auth:sanctum to guard V2 visitor endpoints.
 *
 * For SPA cookie auth: $request->user('visitor') is set via the session guard.
 * For Bearer token auth: $request->user() is set by Sanctum from personal_access_tokens,
 * but the 'visitor' guard is not populated — so we check both.
 */
class EnsureVisitorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        // Try session guard first (SPA cookies), fall back to Sanctum token user
        $visitor = $request->user('visitor') ?? $request->user();

        if (is_null($visitor) || ! $visitor instanceof Visitor) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Visitor authentication required',
                ],
                'meta' => [
                    'api_version' => 'v2',
                ],
            ], 401);
        }

        // Ensure controllers can consistently use $request->user('visitor')
        if (is_null($request->user('visitor'))) {
            auth()->guard('visitor')->setUser($visitor);
        }

        return $next($request);
    }
}
