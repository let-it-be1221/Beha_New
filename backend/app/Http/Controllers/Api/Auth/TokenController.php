<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

/**
 * API Token Controller — issues Sanctum tokens for mobile apps.
 * Spec §37.
 */
class TokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:64'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $data['login'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active || $user->isLocked()) {
            throw ValidationException::withMessages([
                'login' => ['This account is not active or is temporarily locked.'],
            ]);
        }

        if ($user->must_change_password) {
            return response()->json([
                'message' => 'You must change your password before issuing an API token.',
                'redirect' => route('password.force-change'),
            ], 403);
        }

        $token = $user->createToken($data['device_name'])->plainTextToken;
        $user->recordAudit('api.token.issue', category: 'auth');

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'          => $user->id,
                'official_id' => $user->official_id,
                'username'    => $user->username,
                'email'       => $user->email,
                'level'       => $user->level,
                'roles'       => $user->roles->pluck('name'),
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $request->user()->recordAudit('api.token.revoke', category: 'auth');
        return response()->json(['message' => 'Token revoked.']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => new \App\Http\Resources\UserResource($request->user()),
        ]);
    }

    public function notifications(Request $request)
    {
        return response()->json([
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }
}
