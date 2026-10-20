<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    /**
     * GET /api/profile — the logged-in user's account plus their civic
     * profile. `profile` is null for accounts that never got a residents
     * row (e.g. self-registered before the barangay recorded them); the
     * frontend shows a "visit the barangay office" message in that case.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($request->user())]);
    }

    /**
     * PATCH /api/profile — updates only the fields UpdateProfileRequest
     * allows. Everything else on the record stays admin-managed.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $resident = $user->resident;

        if (!$resident) {
            return response()->json([
                'message' => "Your barangay profile hasn't been set up yet. Please visit the barangay office.",
            ], 404);
        }

        $resident->update($request->validated());

        return response()->json(['data' => $this->payload($user->fresh('resident'))]);
    }

    /**
     * PUT /api/profile/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        // Sign out every other device/session, so a stolen or forgotten
        // login stops working the moment the password changes. The token
        // making this request stays valid so the user isn't kicked out.
        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $user->tokens()->where('id', '!=', $current->id)->delete();
        }

        return response()->json(['data' => ['updated' => true]]);
    }

    private function payload(User $user): array
    {
        $resident = $user->resident;

        return [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'profile' => $resident ? [
                ...$resident->only([
                    'purok',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'place_of_birth',
                    'citizenship',
                    'religion',
                    'blood_type',
                    'mother_first_name',
                    'mother_middle_name',
                    'mother_last_name',
                    'mother_occupation',
                    'father_first_name',
                    'father_middle_name',
                    'father_last_name',
                    'father_suffix',
                    'father_occupation',
                    'spouse_first_name',
                    'spouse_middle_name',
                    'spouse_last_name',
                    'spouse_suffix',
                    'number_of_children',
                    'emergency_contact_first_name',
                    'emergency_contact_middle_name',
                    'emergency_contact_last_name',
                    'emergency_contact_suffix',
                    'emergency_contact_number',
                ]),
                'date_of_birth' => $resident->date_of_birth?->toDateString(),
                'sex' => $resident->sex?->value,
                'sex_label' => $resident->sex?->label(),
                'age' => $resident->age,
                'full_name' => $resident->full_name,
                'mother_full_name' => $resident->mother_full_name,
                'father_full_name' => $resident->father_full_name,
            ] : null,
        ];
    }
}