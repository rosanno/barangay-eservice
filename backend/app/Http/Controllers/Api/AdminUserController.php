<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * List all non-resident (staff/admin) accounts, plus optional
     * resident search for the admin's "manage residents" screen.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Admin creates a new staff or admin account.
     * Residents cannot self-register into these roles.
     */
    public function store(StoreAdminUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 'active',
            'contact_number' => $request->contact_number,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => new UserResource($user),
        ], 201);
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot change your own status.',
            ], 422);
        }

        $user->update(['status' => $request->status]);

        return new UserResource($user);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Account deleted.',
        ]);
    }
}
