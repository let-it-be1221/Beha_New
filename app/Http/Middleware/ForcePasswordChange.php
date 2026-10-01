<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForcePasswordChange — redirects users with must_change_password=true
 * to the /password/force-change page (spec §19).
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $allowed = ['password.force-change', 'password.force-change.post', 'logout'];
            if (!in_array($request->route()?->getName(), $allowed, true)) {
                return redirect()->route('password.force-change')
                    ->with('warning', 'You must change your temporary password before continuing.');
            }
        }

        return $next($request);
    }
}
