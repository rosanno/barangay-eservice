<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ResidentLookupController extends Controller
{
    /**
     * GET /api/admin/residents/lookup?search=maria
     *
     * Deliberately separate from whatever your existing "Residents" admin
     * list controller does — this only returns the handful of fields the
     * new-request picker needs, and excludes admin/staff accounts so a
     * staff member can't accidentally file a request "for" another staff
     * member or themselves.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->string('search'));

        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $residents = User::query()
            ->whereNotIn('role', ['admin', 'staff'])
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json(['data' => $residents]);
    }
}