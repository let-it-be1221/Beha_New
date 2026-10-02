<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Level middleware — `level:3` or `level:3,4,5` (minimum-level check).
 * Spec §3 — vertical sales hierarchy enforcement.
 */
class RequireLevel
{
    public function handle(Request $request, Closure $next, string ...$levels): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $allowed = array_map('intval', $levels);
        if (!in_array((int) $user->level, $allowed, true)) {
            abort(403, 'Your level does not allow access to this resource.');
        }

        return $next($request);
    }
}
