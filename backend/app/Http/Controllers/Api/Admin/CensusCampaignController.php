<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CensusCampaign;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CensusCampaignController extends Controller
{
    public function index(Request $request)
    {
        $q = CensusCampaign::query()
            ->withCount([
                'targets as total_households',
                'targets as surveyed_households' => fn($t) => $t->where('status', 'surveyed'),
            ])
            ->with(['puroks:id,census_campaign_id,purok', 'enumerators:id,name'])
            ->latest();

        if ($s = $request->query('search'))
            $q->where('name', 'like', "%{$s}%");
        if ($status = $request->query('status'))
            $q->where('status', $status);

        return response()->json(['data' => $q->paginate($request->integer('per_page', 15))]);
    }

    public function show(CensusCampaign $campaign)
    {
        return response()->json(['data' => $this->detail($campaign)]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $campaign = DB::transaction(function () use ($data, $request) {
            $campaign = CensusCampaign::create([
                ...collect($data)->only(['name', 'description', 'start_date', 'end_date'])->all(),
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);
            $this->syncRelations($campaign, $data);
            return $campaign;
        });

        return response()->json(['data' => $this->detail($campaign)], 201);
    }

    public function update(Request $request, CensusCampaign $campaign)
    {
        abort_if($campaign->status === 'completed', 422, 'Completed campaigns cannot be edited.');
        $data = $this->validated($request);

        DB::transaction(function () use ($campaign, $data) {
            $campaign->update(collect($data)->only(['name', 'description', 'start_date', 'end_date'])->all());
            $this->syncRelations($campaign, $data);
            if ($campaign->status === 'active')
                $campaign->syncTargets();
        });

        return response()->json(['data' => $this->detail($campaign)]);
    }

    public function updateStatus(Request $request, CensusCampaign $campaign)
    {
        $to = $request->validate([
            'status' => ['required', Rule::in(['active', 'paused', 'completed'])],
        ])['status'];

        abort_unless($campaign->canTransitionTo($to), 422, "Cannot change a {$campaign->status} campaign to {$to}.");

        if ($to === 'active' && $campaign->status === 'draft') {
            abort_if($campaign->puroks()->doesntExist(), 422, 'Select at least one target purok first.');
            abort_if($campaign->enumerators()->doesntExist(), 422, 'Assign at least one enumerator first.');
        }

        DB::transaction(function () use ($campaign, $to) {
            if ($to === 'active') {
                $campaign->activated_at ??= Carbon::now();
                $campaign->syncTargets();
            }
            if ($to === 'completed')
                $campaign->completed_at = Carbon::now();
            $campaign->status = $to;
            $campaign->save();
        });

        return response()->json(['data' => $this->detail($campaign)]);
    }

    public function destroy(CensusCampaign $campaign)
    {
        abort_if($campaign->status === 'active', 422, 'Pause or complete the campaign before deleting it.');
        $campaign->delete();
        return response()->json(['message' => 'Campaign deleted.']);
    }

    /** Puroks + staff used by the create/edit form. */
    public function options()
    {
        return response()->json([
            'data' => [
                'puroks' => Household::whereNotNull('purok')->distinct()->orderBy('purok')->pluck('purok'),
                // Adjust the role filter to match your RBAC setup
                'enumerators' => User::whereIn('role', ['staff', 'admin'])->orderBy('name')->get(['id', 'name']),
            ]
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'puroks' => ['required', 'array', 'min:1'],
            'puroks.*' => ['string', 'max:100'],
            'enumerator_ids' => ['required', 'array', 'min:1'],
            'enumerator_ids.*' => ['integer', 'exists:users,id'],
        ]);
    }

    private function syncRelations(CensusCampaign $campaign, array $data): void
    {
        $campaign->puroks()->whereNotIn('purok', $data['puroks'])->delete();
        foreach (array_unique($data['puroks']) as $purok) {
            $campaign->puroks()->firstOrCreate(['purok' => $purok]);
        }
        $campaign->enumerators()->sync($data['enumerator_ids']);
    }

    private function detail(CensusCampaign $campaign): CensusCampaign
    {
        return $campaign
            ->loadCount([
                'targets as total_households',
                'targets as surveyed_households' => fn($t) => $t->where('status', 'surveyed'),
            ])
            ->load(['puroks:id,census_campaign_id,purok', 'enumerators:id,name', 'creator:id,name']);
    }
}
