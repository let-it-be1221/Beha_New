<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * SanctumAuthController — SPA cookie-based auth (spec §37).
 *
 * Flow:
 *   1. SPA calls GET /sanctum/csrf-cookie to establish CSRF + session cookies
 *   2. SPA POSTs credentials to /api/v1/auth/login
 *   3. Server authenticates via web guard (session cookie set)
 *   4. Subsequent /api/v1/* requests are auto-authenticated via cookie
 *   5. POST /api/v1/auth/logout destroys session
 */
class SanctumAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('login', 'password');
        $field       = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $credentials['login'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $user->registerFailedLogin();
            }
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => __('auth.inactive'),
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'login' => __('auth.locked'),
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $user->registerLogin($request->ip());

        return response()->json([
            'message'              => 'Logged in',
            'must_change_password' => $user->must_change_password,
            'user'                 => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->recordAudit('logout', category: 'auth');
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['user' => null], 401);
        }

        // Enforce must_change_password for SPA too (spec §19)
        if ($user->must_change_password) {
            return response()->json([
                'user'                  => new UserResource($user),
                'must_change_password'  => true,
                'message'               => __('auth.must_change'),
            ], 403);
        }

        return response()->json([
            'user'                  => new UserResource($user),
            'must_change_password'  => false,
        ]);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', 'min:' . config('beha.password_policy.min_length', 12)],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password is incorrect.'],
            ]);
        }

        $user->password = $data['password'];
        $user->must_change_password = false;
        $user->save();

        $user->recordAudit('password.change', category: 'auth');

        return response()->json(['message' => 'Password changed.']);
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $status = \Illuminate\Support\Facades\Password::sendResetLink($data);
        return response()->json(['status' => __($status)]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', 'min:' . config('beha.password_policy.min_length', 12)],
        ]);

        $status = \Illuminate\Support\Facades\Password::reset($data, function ($user, $password) {
            $user->password = $password;
            $user->must_change_password = false;
            $user->save();
            $user->recordAudit('password.reset', category: 'auth');
        });

        return response()->json(['status' => __($status)]);
    }

    public function notifications(Request $request)
    {
        return response()->json([
            'notifications' => $request->user()->notifications()->paginate(20),
            'unread_count'  => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
