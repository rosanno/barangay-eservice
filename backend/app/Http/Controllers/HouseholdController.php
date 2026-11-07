<?php

namespace App\Http\Controllers;

use App\Http\Requests\HouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Resident-facing: each account manages exactly one household profile.
 */
class HouseholdController extends Controller
{
    private const HOUSEHOLD_FIELDS = [
        'purok',
        'street_address',
        'housing_tenure',
        'house_material',
        'water_source',
        'toilet_type',
        'has_electricity',
        'income_bracket',
        'is_4ps',
    ];

    private function mine(Request $request): ?Household
    {
        return Household::where('user_id', $request->user()->id)->with('members')->first();
    }

    public function show(Request $request): JsonResponse
    {
        $household = $this->mine($request);

        return response()->json(['data' => $household ? new HouseholdResource($household) : null]);
    }

    public function store(HouseholdRequest $request): JsonResponse
    {
        if ($this->mine($request)) {
            return response()->json(['message' => 'You already have a household profile. Update it instead.'], 409);
        }

        $household = DB::transaction(function () use ($request) {
            $household = Household::create([
                ...$request->only(self::HOUSEHOLD_FIELDS),
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'consent_at' => now(),
            ]);
            $household->members()->createMany($request->validated('members'));

            return $household;
        });

        return response()->json(['data' => new HouseholdResource($household->load('members'))], 201);
    }

    public function update(HouseholdRequest $request): JsonResponse
    {
        $household = $this->mine($request);
        abort_if(!$household, 404, 'No household profile found.');
        abort_if($household->status === 'rejected', 403, 'This profile was rejected. Please visit the barangay hall.');

        DB::transaction(function () use ($request, $household) {
            // Any resident edit sends the profile back for (re)verification
            $household->update([
                ...$request->only(self::HOUSEHOLD_FIELDS),
                'status' => 'pending',
                'remarks' => null,
                'verified_by' => null,
                'verified_at' => null,
                'consent_at' => now(),
            ]);

            $household->members()->delete();
            $household->members()->createMany($request->validated('members'));
        });

        return response()->json(['data' => new HouseholdResource($household->fresh('members'))]);
    }
}
