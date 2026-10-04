<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    private const SORTABLE = ['name', 'email', 'role', 'last_login_at', 'created_at'];

    public function index(Request $request)
    {
        $request->validate([
            'role' => ['nullable', 'in:staff,admin'],
            'status' => ['nullable', 'in:active,inactive'],
            'sort_by' => ['nullable', 'in:' . implode(',', self::SORTABLE)],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = User::query()->whereIn('role', ['staff', 'admin']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $query->orderBy($request->input('sort_by', 'name'), $request->input('sort_dir', 'asc'));

        $base = User::whereIn('role', ['staff', 'admin']);

        return StaffResource::collection($query->paginate($request->integer('per_page', 10)))
            ->additional([
                'summary' => [
                    'admins' => (clone $base)->where('role', 'admin')->count(),
                    'staff' => (clone $base)->where('role', 'staff')->count(),
                    'inactive' => (clone $base)->where('is_active', false)->count(),
                ]
            ]);
    }

    public function store(StaffRequest $request): JsonResponse
    {
        $tempPassword = Str::password(12, symbols: false);

        $user = User::create([
            ...$request->validated(),
            'password' => Hash::make($tempPassword),
            'is_active' => true,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        // The temporary password is returned once so the admin can hand it over.
        return (new StaffResource($user))
            ->additional(['temporary_password' => $tempPassword])
            ->response()->setStatusCode(201);
    }

    public function show(User $user): StaffResource
    {
        $this->ensureStaffOrAdmin($user);

        return new StaffResource($user);
    }

    public function update(StaffRequest $request, User $user): StaffResource
    {
        $this->ensureStaffOrAdmin($user);

        if ($user->role === 'admin' && $request->input('role') !== 'admin') {
            $this->ensureSafeToRemoveAdmin($request, $user, 'change your own role');
        }

        $user->update($request->validated());

        return new StaffResource($user->refresh());
    }

    public function updateStatus(Request $request, User $user): StaffResource
    {
        $this->ensureStaffOrAdmin($user);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        if (!$data['is_active'] && $user->role === 'admin') {
            $this->ensureSafeToRemoveAdmin($request, $user, 'deactivate your own account');
        } elseif (!$data['is_active'] && $request->user()->id === $user->id) {
            abort(422, "You can't deactivate your own account.");
        }

        $user->update(['is_active' => $data['is_active']]);

        // Kick the user out immediately when deactivated (Sanctum tokens).
        if (!$data['is_active'] && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return new StaffResource($user->refresh());
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->ensureStaffOrAdmin($user);

        if ($request->user()->id === $user->id) {
            abort(422, 'Use your profile page to change your own password.');
        }

        $tempPassword = Str::password(12, symbols: false);
        $user->update(['password' => Hash::make($tempPassword), 'must_change_password' => true]);

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return response()->json(['temporary_password' => $tempPassword]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->ensureStaffOrAdmin($user);

        if ($user->role === 'admin') {
            $this->ensureSafeToRemoveAdmin($request, $user, 'delete your own account');
        } elseif ($request->user()->id === $user->id) {
            abort(422, "You can't delete your own account.");
        }

        $user->delete();

        return response()->json(null, 204);
    }

    /** This module only manages staff/admin accounts, never residents. */
    private function ensureStaffOrAdmin(User $user): void
    {
        abort_unless(in_array($user->role, ['staff', 'admin'], true), 404);
    }

    /** Block self-lockout and never leave the barangay without an active admin. */
    private function ensureSafeToRemoveAdmin(Request $request, User $target, string $selfMessage): void
    {
        if ($request->user()->id === $target->id) {
            abort(422, "You can't {$selfMessage}.");
        }

        $otherActiveAdmins = User::where('role', 'admin')
            ->where('is_active', true)
            ->where('id', '!=', $target->id)
            ->exists();

        abort_unless($otherActiveAdmins, 422, 'At least one active admin must remain.');
    }
}