<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('login', 'password');
        $field       = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $credentials['login'])->first();

        if (!$user || !\Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $user->registerFailedLogin();
            }
            throw ValidationException::withMessages([
                'login' => __('These credentials do not match our records.'),
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => __('This account is inactive. Contact the system administrator.'),
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'login' => __('Too many failed attempts. Try again later.'),
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $user->registerLogin($request->ip());

        // First login → force password change (spec §19)
        if ($user->must_change_password) {
            return redirect()->route('password.force-change')
                ->with('warning', 'You must change your temporary password before continuing.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->recordAudit('logout', category: 'auth');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
