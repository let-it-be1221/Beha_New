<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confidential-access middleware — guards routes that decrypt
 * / display confidential IDs (spec §6).
 *
 * Used as a route-level guard in addition to the UserPolicy::viewConfidential check.
 */
class RequireConfidentialAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $allowed = config('beha.confidential_id_viewers', []);
        if (!$user->hasAnyRole($allowed)) {
            abort(403, 'You are not authorized to view confidential information.');
        }

        return $next($request);
    }
}
