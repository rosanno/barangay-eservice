<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->when(
                $request->filled('search'),
                fn($q) => $q->where(function ($sub) use ($request) {
                    $term = '%' . $request->string('search') . '%';
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                })
            )
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        // One grouped count query instead of one query per resident row —
        // avoids an N+1 for "how many document requests has this person made".
        $requestCounts = DocumentRequest::query()
            ->whereIn('user_id', $residents->pluck('id'))
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return response()->json([
            'data' => $residents->getCollection()->map(fn(User $resident) => [
                'id' => $resident->id,
                'name' => $resident->name,
                'email' => $resident->email,
                'joined_at' => optional($resident->created_at)->toIso8601String(),
                'request_count' => $requestCounts->get($resident->id, 0),
            ]),
            'meta' => [
                'current_page' => $residents->currentPage(),
                'last_page' => $residents->lastPage(),
                'per_page' => $residents->perPage(),
                'total' => $residents->total(),
            ],
        ]);
    }
}