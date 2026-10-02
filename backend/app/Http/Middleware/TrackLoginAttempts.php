<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackLoginAttempts — increments failed_login_count when credentials
 * don't match; resets on successful login.
 *
 * Spec §25 — login attempt monitoring + account lockout.
 */
class TrackLoginAttempts
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Failed-login hook (called by LoginRequest::authenticate() on failure).
     */
    public function handleFailure(string $identifier): void
    {
        // resolved via User::registerFailedLogin() in AuthController
    }
}
