<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class PreferenceController extends Controller
{
    /**
     * GET /api/preferences
     * Named "preferences" rather than "settings" so it can't be confused
     * with the admin-only GET /api/admin/settings (barangay information).
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($request->user())]);
    }

    /**
     * PATCH /api/preferences
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_request_updates' => ['required', 'boolean'],
        ]);

        UserSetting::forUser($request->user())->update($validated);

        return response()->json(['data' => $this->payload($request->user())]);
    }

    /**
     * POST /api/preferences/sign-out-others
     * Revokes every login token except the one making this request.
     */
    public function signOutOthers(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();
        $count = 0;

        if ($current instanceof PersonalAccessToken) {
            $count = $user->tokens()->where('id', '!=', $current->id)->delete();
        }

        return response()->json(['data' => ['signed_out' => $count]]);
    }

    private function payload(User $user): array
    {
        return [
            'email' => $user->email,
            'email_request_updates' => UserSetting::forUser($user)->email_request_updates,
        ];
    }
}