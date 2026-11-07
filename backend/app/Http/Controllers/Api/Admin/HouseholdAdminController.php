<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use App\Models\HouseholdMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseholdAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = Household::query()
            ->with(['head'])
            ->withCount('members')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('purok'), fn($q) => $q->where('purok', $request->purok))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->search . '%';
                $q->where(function ($w) use ($s) {
                    $w->where('household_number', 'like', $s)
                        ->orWhere('street_address', 'like', $s)
                        ->orWhereHas('members', fn($m) => $m->where(
                            fn($n) => $n->where('first_name', 'like', $s)->orWhere('last_name', 'like', $s)
                        ));
                });
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json([
            'data' => [
                'items' => HouseholdResource::collection(collect($paginator->items()))->resolve(),
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ]
        ]);
    }

    public function show(Household $household): JsonResponse
    {
        $household->load(['members', 'user', 'verifier'])->loadCount('members');

        return response()->json(['data' => new HouseholdResource($household)]);
    }

    public function updateStatus(Request $request, Household $household): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Household::STATUSES)],
            'remarks' => ['required_if:status,needs_update,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        $verified = $data['status'] === 'verified';

        $household->update([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
            'verified_by' => $verified ? $request->user()->id : null,
            'verified_at' => $verified ? now() : null,
        ]);

        return response()->json(['data' => new HouseholdResource($household->load(['members', 'user', 'verifier']))]);
    }

    public function destroy(Household $household): JsonResponse
    {
        $household->delete(); // soft delete

        return response()->json(['message' => 'Household removed.']);
    }

    /** Census summary — counts only verified households. */
    public function stats(): JsonResponse
    {
        $age = 'TIMESTAMPDIFF(YEAR, birthdate, CURDATE())';
        $members = fn() => HouseholdMember::query()
            ->whereHas('household', fn($q) => $q->where('status', 'verified'));

        return response()->json([
            'data' => [
                'households' => Household::count(),
                'verified_households' => Household::where('status', 'verified')->count(),
                'pending_households' => Household::where('status', 'pending')->count(),
                'four_ps_households' => Household::where('status', 'verified')->where('is_4ps', true)->count(),

                'population' => $members()->count(),
                'male' => $members()->where('sex', 'male')->count(),
                'female' => $members()->where('sex', 'female')->count(),
                'minors' => $members()->whereRaw("$age < 18")->count(),
                'seniors' => $members()->whereRaw("$age >= 60")->count(),
                'pwd' => $members()->where('is_pwd', true)->count(),
                'solo_parents' => $members()->where('is_solo_parent', true)->count(),
                'registered_voters' => $members()->where('is_registered_voter', true)->count(),

                'by_purok' => Household::where('status', 'verified')
                    ->select('purok', DB::raw('COUNT(*) as households'))
                    ->groupBy('purok')->orderBy('purok')->get(),
            ]
        ]);
    }

    /** One CSV row per resident (verified households only). */
    public function export(): StreamedResponse
    {
        $headers = [
            'Household No',
            'Purok',
            'Address',
            'Last Name',
            'First Name',
            'Middle Name',
            'Suffix',
            'Relationship',
            'Head',
            'Birthdate',
            'Age',
            'Sex',
            'Civil Status',
            'Education',
            'Occupation',
            'PWD',
            'Solo Parent',
            'Registered Voter',
            '4Ps Household',
        ];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ñ correctly
            fputcsv($out, $headers);

            HouseholdMember::with('household')
                ->whereHas('household', fn($q) => $q->where('status', 'verified'))
                ->lazyById(500)
                ->each(function ($m) use ($out) {
                    fputcsv($out, [
                        $m->household->household_number,
                        $m->household->purok,
                        $m->household->street_address,
                        $m->last_name,
                        $m->first_name,
                        $m->middle_name,
                        $m->suffix,
                        $m->relationship_to_head,
                        $m->is_head ? 'Yes' : 'No',
                        $m->birthdate?->toDateString(),
                        $m->age,
                        $m->sex,
                        $m->civil_status,
                        $m->education,
                        $m->occupation,
                        $m->is_pwd ? 'Yes' : 'No',
                        $m->is_solo_parent ? 'Yes' : 'No',
                        $m->is_registered_voter ? 'Yes' : 'No',
                        $m->household->is_4ps ? 'Yes' : 'No',
                    ]);
                });

            fclose($out);
        }, 'household-census-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
