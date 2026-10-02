<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['team', 'roles'])
            ->visibleTo($request->user())
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('official_id', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);
        $user->load(['team', 'roles', 'permissions']);
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'username'           => ['sometimes', 'string', 'max:64', Rule::unique('users')->ignore($user->id)],
            'email'              => ['sometimes', 'email:rfc,dns', Rule::unique('users')->ignore($user->id)],
            'is_active'          => ['sometimes', 'boolean'],
            'level'              => ['sometimes', 'integer', 'min:1', 'max:5'],
            'current_team_id'    => ['sometimes', 'nullable', 'exists:teams,id'],
        ]);

        $user->update($data);
        $user->recordAudit('user.update', entity: User::class, entityId: $user->id, new: $data, category: 'rbac');
        return new UserResource($user->fresh());
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();
        $user->recordAudit('user.delete', entity: User::class, entityId: $user->id, category: 'rbac');
        return response()->json(['message' => 'User deactivated.']);
    }
}
