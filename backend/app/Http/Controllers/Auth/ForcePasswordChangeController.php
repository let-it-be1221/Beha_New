<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * ForcePasswordChange — spec §19.
 * The user MUST change their temp password before accessing the dashboard.
 */
class ForcePasswordChangeController extends Controller
{
    public function show(Request $request)
    {
        if (!$request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }
        return view('auth.force-change');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min((int) config('beha.password_policy.min_length', 12))
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $user = $request->user();
        $user->password = $data['password'];
        $user->must_change_password = false;
        $user->save();

        $user->recordAudit('password.change', category: 'auth');

        return redirect()->route('dashboard')
            ->with('success', 'Your password has been changed. Welcome to Beha.');
    }
}
