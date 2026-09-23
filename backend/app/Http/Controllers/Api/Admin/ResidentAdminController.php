<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResidentRequest;
use App\Models\DocumentRequest;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResidentAdminController extends Controller
{
    /**
     * GET /api/admin/residents?search=&per_page=&page=
     *
     * Deliberately separate from whatever your existing AdminUserController
     * (for "Staff & admins") does — I don't have that file's contents in
     * this conversation, so rather than guess at its structure this stands
     * alone. Merge the two later if you'd rather share one controller.
     */
    public function index(Request $request): JsonResponse
    {
        $residents = User::query()
            ->whereNotIn('role', ['admin', 'staff'])
            ->with('resident')
            ->when(
                $request->filled('search'),
                fn($q) => $q->where(function ($sub) use ($request) {
                    $term = '%' . $request->string('search') . '%';
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhereHas('resident', function ($r) use ($term) {
                            $r->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('purok', 'like', $term);
                        });
                })
            )
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        $requestCounts = DocumentRequest::query()
            ->whereIn('user_id', $residents->pluck('id'))
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return response()->json([
            'data' => $residents->getCollection()->map(function (User $user) use ($requestCounts) {
                $profile = $user->resident;

                return [
                    'id' => $user->id,
                    'name' => $profile?->full_name ?? $user->name,
                    'email' => $user->email,
                    'purok' => $profile?->purok,
                    'sex' => $profile?->sex?->label(),
                    'age' => $profile?->age,
                    'has_profile' => $profile !== null,
                    'joined_at' => optional($user->created_at)->toIso8601String(),
                    'request_count' => $requestCounts->get($user->id, 0),
                ];
            }),
            'meta' => [
                'current_page' => $residents->currentPage(),
                'last_page' => $residents->lastPage(),
                'per_page' => $residents->perPage(),
                'total' => $residents->total(),
            ],
        ]);
    }

    /**
     * POST /api/admin/residents
     *
     * Creates the login account (users) and the civic profile (residents)
     * together, in one transaction — either both are created or neither is,
     * so you never end up with an orphaned account with no profile or a
     * profile with no way to log in.
     */
    public function store(StoreResidentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $resident = DB::transaction(function () use ($validated) {
            $fullName = trim(str_replace(
                '  ',
                ' ',
                "{$validated['first_name']} " . ($validated['middle_name'] ?? '') . " {$validated['last_name']}"
            ));

            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'resident',
            ]);

            return Resident::create([
                ...collect($validated)->except(['email', 'password', 'password_confirmation'])->all(),
                'user_id' => $user->id,
            ])->load('user');
        });

        return response()->json([
            'data' => [
                'id' => $resident->user->id,
                'name' => $resident->full_name,
                'email' => $resident->user->email,
                'purok' => $resident->purok,
                'sex' => $resident->sex->label(),
                'age' => $resident->age,
                'has_profile' => true,
                'joined_at' => optional($resident->user->created_at)->toIso8601String(),
                'request_count' => 0,
            ],
        ], 201);
    }
}