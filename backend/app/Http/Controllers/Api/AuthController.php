<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Public self-registration — always creates a "resident" account.
     * Admin/staff accounts are created separately via AdminUserController.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'resident',
            'status' => 'active',
            'contact_number' => $request->contact_number,
            'address' => $request->address,
        ]);

        $token = $user->createToken('resident-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Shared login for residents, staff, and admins.
     * The role returned lets the frontend route to the right dashboard.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return response()->json([
                'message' => 'This account is not active. Contact the barangay office.',
            ], 403);
        }

        // Revoke previous tokens for this device/app name to avoid pile-up
        $user->tokens()->where('name', 'auth-token')->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(\Illuminate\Http\Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(\Illuminate\Http\Request $request)
    {
        return new UserResource($request->user());
    }
}
