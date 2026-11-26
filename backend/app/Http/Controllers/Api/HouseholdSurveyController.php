<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CensusCampaign;
use App\Models\CensusCampaignHousehold;
use App\Models\Household;
use App\Models\HouseholdSurvey as S;
use App\Models\Resident; // ASSUMPTION: your existing residents model
use Carbon\Carbon;
use Closure;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HouseholdSurveyController extends Controller implements HasMiddleware
{
    /** Only staff and admins may use the survey endpoints. */
    public static function middleware(): array
    {
        return [
            function (Request $request, Closure $next) {
                abort_unless(in_array($request->user()?->role, ['staff', 'admin'], true), 403, 'Staff access only.');
                return $next($request);
            },
        ];
    }

    /* ---------- helpers ---------- */

    // ASSUMPTION: admins verify surveys. Swap for your RBAC check.
    private function isVerifier(Request $r): bool
    {
        return $r->user()->role === 'admin';
    }

    private function canView(Request $r, S $s): bool
    {
        return $this->isVerifier($r) || $s->enumerator_id === $r->user()->id;
    }

    private function editableByOwner(Request $r, S $s): void
    {
        abort_unless($s->enumerator_id === $r->user()->id, 403, 'This survey belongs to another enumerator.');
        abort_unless($s->isEditable(), 422, 'This survey can no longer be edited.');
    }

    private function detail(S $s): S
    {
        return $s->load(['members', 'campaign:id,name,status', 'enumerator:id,name', 'verifier:id,name']);
    }

    /* ---------- lookups ---------- */

    public function options(Request $request)
    {
        $campaigns = CensusCampaign::whereIn('status', ['active', 'paused', 'completed'])
            ->when(!$this->isVerifier($request), fn($q) => $q->whereHas(
                'enumerators',
                fn($e) => $e->where('users.id', $request->user()->id)
            ))
            ->orderByDesc('id')->get(['id', 'name', 'status']);

        $lists = collect(S::OPTIONS)->map(fn($opts) => collect($opts)
            ->map(fn($title, $value) => compact('value', 'title'))->values());

        return response()->json([
            'data' => $lists->merge([
                'campaigns' => $campaigns,
                'puroks' => Household::whereNotNull('purok')->distinct()->orderBy('purok')->pluck('purok'),
                'extra_questions' => S::EXTRA_QUESTIONS,
            ])
        ]);
    }

    public function residents(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2)
            return response()->json(['data' => []]);

        $rows = Resident::where(fn($w) => $w->where('first_name', 'like', "%{$q}%")
            ->orWhere('last_name', 'like', "%{$q}%"))
            ->limit(10)->get()->map(fn($r) => $this->residentPayload($r));

        return response()->json(['data' => $rows]);
    }

    // ASSUMPTION: residents have these columns. Adjust names here only.
    private function residentPayload($r): array
    {
        return [
            'id' => $r->id,
            'first_name' => $r->first_name,
            'middle_name' => $r->middle_name,
            'last_name' => $r->last_name,
            'suffix' => $r->suffix ?? null,
            'birthdate' => $r->birthdate ? Carbon::parse($r->birthdate)->format('Y-m-d') : null,
            'sex' => $r->sex ?? null,
            'civil_status' => $r->civil_status ?? null,
        ];
    }

    /* ---------- list (one row per target household) ---------- */

    public function index(Request $request)
    {
        $user = $request->user();
        $verifier = $this->isVerifier($request);

        $q = CensusCampaignHousehold::query()
            ->with([
                'household',
                'campaign:id,name,status',
                'survey:id,census_campaign_household_id,enumerator_id,status,head_name,revision_count',
                'survey.enumerator:id,name',
            ])
            // Skip targets left behind by soft-deleted campaigns and campaigns still in draft
            ->whereHas('campaign', fn($c) => $c->whereIn('status', ['active', 'paused', 'completed']))
            ->when(!$verifier, fn($q) => $q->whereHas('campaign.enumerators', fn($e) => $e->where('users.id', $user->id)))
            ->when($request->campaign_id, fn($q, $v) => $q->where('census_campaign_id', $v))
            ->when($request->purok, fn($q, $v) => $q->whereHas('household', fn($h) => $h->where('purok', $v)))
            ->when($request->status, function ($q, $v) {
                $v === 'not_started'
                    ? $q->whereDoesntHave('survey')
                    : $q->whereHas('survey', fn($s) => $s->where('status', $v));
            })
            ->when($request->search, function ($q, $v) {
                $q->where(fn($w) => $w
                    ->whereHas('household', fn($h) => $h->where('household_number', 'like', "%{$v}%")->orWhere('address', 'like', "%{$v}%"))
                    ->orWhereHas('survey', fn($s) => $s->where('head_name', 'like', "%{$v}%")));
            })
            ->orderByDesc('id');

        $page = $q->paginate($request->integer('per_page', 15))->through(fn($t) => [
            'id' => $t->id,
            'campaign' => $t->campaign,
            'household' => [
                'id' => $t->household?->id,
                'household_number' => $t->household?->household_number,
                'address' => $t->household?->address,
                'purok' => $t->household?->purok,
            ],
            'survey' => $t->survey,
            'status' => $t->survey?->status ?? 'not_started',
        ]);

        return response()->json(['data' => $page]);
    }

    /* ---------- admin verification queue ---------- */

    public function queue(Request $request)
    {
        abort_unless($this->isVerifier($request), 403);

        $status = $request->query('status', 'for_verification'); // for_verification|needs_revision|approved|all

        $base = S::query()->whereHas('campaign')->where('status', '!=', 'draft'); // whereHas skips soft-deleted campaigns

        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $q = (clone $base)
            ->with(['campaign:id,name', 'enumerator:id,name'])
            ->withCount('members')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($request->campaign_id, fn($q, $v) => $q->where('census_campaign_id', $v))
            ->when($request->purok, fn($q, $v) => $q->where('purok', $v))
            ->when($request->search, fn($q, $v) => $q->where(fn($w) => $w
                ->where('head_name', 'like', "%{$v}%")->orWhere('household_number', 'like', "%{$v}%")));

        $status === 'for_verification' ? $q->oldest('submitted_at') : $q->latest('updated_at'); // oldest first = fair queue

        $page = $q->paginate($request->integer('per_page', 15))->through(fn($s) => [
            'id' => $s->id,
            'status' => $s->status,
            'household_number' => $s->household_number,
            'head_name' => $s->head_name,
            'purok' => $s->purok,
            'members_count' => $s->members_count,
            'campaign' => $s->campaign?->name,
            'enumerator' => $s->enumerator?->name,
            'submitted_at' => $s->submitted_at,
            'revision_count' => $s->revision_count,
        ]);

        return response()->json([
            'data' => [
                'surveys' => $page,
                'counts' => [
                    'for_verification' => $counts['for_verification'] ?? 0,
                    'needs_revision' => $counts['needs_revision'] ?? 0,
                    'approved' => $counts['approved'] ?? 0,
                ],
            ]
        ]);
    }

    /* ---------- start / read / save ---------- */

    public function store(Request $request)
    {
        $id = $request->validate(['census_campaign_household_id' => ['required', 'exists:census_campaign_households,id']])['census_campaign_household_id'];
        $target = CensusCampaignHousehold::with(['campaign.enumerators', 'household', 'survey'])->findOrFail($id);

        if ($target->survey) {
            abort_unless($this->canView($request, $target->survey), 403, 'This household is being surveyed by another enumerator.');
            return response()->json(['data' => $this->detail($target->survey)]);
        }

        abort_unless($target->campaign->status === 'active', 422, 'This campaign is not active.');
        abort_unless($target->campaign->enumerators->contains($request->user()->id), 403, 'You are not assigned to this campaign.');

        $h = $target->household;
        $survey = new S([
            'household_number' => $h?->household_number,
            'address' => $h?->address,
            'purok' => $h?->purok,
        ]);
        $survey->census_campaign_id = $target->census_campaign_id;
        $survey->census_campaign_household_id = $target->id;
        $survey->household_id = $target->household_id;
        $survey->enumerator_id = $request->user()->id;
        $survey->save();

        return response()->json(['data' => $this->detail($survey)], 201);
    }

    public function show(Request $request, S $survey)
    {
        abort_unless($this->canView($request, $survey), 403);
        return response()->json(['data' => $this->detail($survey)]);
    }

    /** Save draft / autosave: lenient validation, partial payloads allowed. */
    public function update(Request $request, S $survey)
    {
        $this->editableByOwner($request, $survey);

        $str = ['nullable', 'string', 'max:255'];
        $opt = fn($k) => ['nullable', Rule::in(S::values($k))];

        $data = $request->validate([
            'current_step' => ['nullable', 'integer', 'between:1,5'],
            'household_number' => $str,
            'head_name' => $str,
            'purok' => $str,
            'address' => ['nullable', 'string', 'max:1000'],
            'housing_type' => $opt('housing_type'),
            'ownership_status' => $opt('ownership_status'),
            'housing_condition' => $opt('housing_condition'),
            'water_source' => $opt('water_source'),
            'sanitation' => $opt('sanitation'),
            'has_electricity' => ['nullable', 'boolean'],
            'extra_answers' => ['nullable', 'array'],
            'members' => ['nullable', 'array', 'max:50'],
            'members.*.client_key' => ['required', 'string', 'max:36'],
            'members.*.resident_id' => ['nullable', 'integer'],
            'members.*.is_new_resident' => ['boolean'],
            'members.*.first_name' => $str,
            'members.*.middle_name' => $str,
            'members.*.last_name' => $str,
            'members.*.suffix' => ['nullable', 'string', 'max:20'],
            'members.*.relationship' => $opt('relationship'),
            'members.*.sex' => $opt('sex'),
            'members.*.birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'members.*.civil_status' => $opt('civil_status'),
            'members.*.education' => $opt('education'),
            'members.*.employment_status' => $opt('employment_status'),
            'members.*.occupation' => $str,
            'members.*.is_pwd' => ['boolean'],
            'members.*.is_registered_voter' => ['boolean'],
            'members.*.contact_number' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($survey, $data) {
            $survey->fill(Arr::except($data, ['members']))->save();

            if (array_key_exists('members', $data)) {
                $keys = [];
                foreach ($data['members'] as $m) {
                    $survey->members()->updateOrCreate(['client_key' => $m['client_key']], $m);
                    $keys[] = $m['client_key'];
                }
                $survey->members()->whereNotIn('client_key', $keys)->delete();
            }
        });

        return response()->json(['data' => $this->detail($survey->fresh())]);
    }

    /** Strict validation, then hand over for verification. */
    public function submit(Request $request, S $survey)
    {
        $this->editableByOwner($request, $survey);
        $survey->load('members');
        $d = $survey->toArray();

        $req = fn($k) => ['required', Rule::in(S::values($k))];
        Validator::make($d, [
            'household_number' => ['required', 'string', 'max:100'],
            'head_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'purok' => ['required', 'string'],
            'housing_type' => $req('housing_type'),
            'ownership_status' => $req('ownership_status'),
            'housing_condition' => $req('housing_condition'),
            'water_source' => $req('water_source'),
            'sanitation' => $req('sanitation'),
            'has_electricity' => ['required', 'boolean'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.first_name' => ['required', 'string'],
            'members.*.last_name' => ['required', 'string'],
            'members.*.relationship' => $req('relationship'),
            'members.*.sex' => $req('sex'),
            'members.*.birthdate' => ['required', 'date'],
            'members.*.civil_status' => $req('civil_status'),
            'members.*.education' => $req('education'),
            'members.*.employment_status' => $req('employment_status'),
        ], [], ['members.*.first_name' => 'first name', 'members.*.last_name' => 'last name'])->validate();

        $errors = [];
        $heads = $survey->members->where('relationship', 'head')->count();
        if ($heads !== 1)
            $errors['members'] = ['Exactly one household member must be marked as the household head.'];
        foreach ($survey->members->values() as $i => $m) {
            if (in_array($m->employment_status, ['employed', 'self_employed'], true) && blank($m->occupation)) {
                $errors["members.$i.occupation"] = ["Occupation is required for {$m->first_name} {$m->last_name}."];
            }
        }
        if ($errors)
            throw ValidationException::withMessages($errors);

        $survey->forceFill(['status' => 'for_verification', 'submitted_at' => now(), 'current_step' => 5])->save();

        return response()->json(['data' => $this->detail($survey)]);
    }

    /* ---------- verification ---------- */

    public function comparison(Request $request, S $survey)
    {
        abort_unless($this->isVerifier($request), 403);
        $fields = ['first_name', 'middle_name', 'last_name', 'birthdate', 'sex', 'civil_status'];
        $norm = fn($v) => mb_strtolower(trim((string) $v));

        $rows = $survey->members->map(function ($m) use ($fields, $norm) {
            $row = ['client_key' => $m->client_key, 'name' => trim("{$m->first_name} {$m->last_name}")];

            if ($m->resident_id && ($res = Resident::find($m->resident_id))) {
                $existing = $this->residentPayload($res);
                $diffs = [];
                foreach ($fields as $f) {
                    $mine = $f === 'birthdate' ? $m->birthdate?->format('Y-m-d') : $m->{$f};
                    if ($norm($mine) !== $norm($existing[$f])) {
                        $diffs[] = ['field' => $f, 'survey' => $mine, 'record' => $existing[$f]];
                    }
                }
                return $row + ['result' => $diffs ? 'differences' : 'match', 'differences' => $diffs];
            }

            $possible = Resident::where('first_name', $m->first_name)->where('last_name', $m->last_name)
                ->when($m->birthdate, fn($q) => $q->whereDate('birthdate', $m->birthdate))
                ->limit(3)->get()->map(fn($r) => $this->residentPayload($r));

            return $row + ['result' => $possible->isNotEmpty() ? 'possible_match' : 'new', 'possible' => $possible];
        })->values();

        return response()->json(['data' => $rows]);
    }

    public function verify(Request $request, S $survey)
    {
        abort_unless($this->isVerifier($request), 403);
        abort_unless($survey->status === 'for_verification', 422, 'Only surveys submitted for verification can be reviewed.');
        abort_if($survey->enumerator_id === $request->user()->id, 403, 'You cannot verify your own survey.');

        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'return'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['action'] === 'return' && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages(['notes' => ['Add a note explaining what needs correction.']]);
        }

        DB::transaction(function () use ($survey, $data, $request) {
            $survey->verified_by = $request->user()->id;
            $survey->verified_at = now();
            $survey->verification_notes = $data['notes'] ?? null;

            if ($data['action'] === 'approve') {
                foreach ($survey->members as $m) {
                    if ($m->is_new_resident && !$m->resident_id) {
                        $m->resident_id = $this->createResident($m);
                        $m->save();
                    }
                }
                $survey->status = 'approved';
                $survey->target?->forceFill([
                    'status' => 'surveyed',
                    'surveyed_at' => now(),
                    'enumerator_id' => $survey->enumerator_id,
                ])->save();
            } else {
                $survey->status = 'needs_revision';
                $survey->revision_count++;
            }
            $survey->save();
        });

        return response()->json(['data' => $this->detail($survey->fresh())]);
    }

    // ASSUMPTION: these columns exist on your residents table. Adjust the mapping here.
    private function createResident($m): int
    {
        $r = new Resident();
        $r->forceFill([
            'first_name' => $m->first_name,
            'middle_name' => $m->middle_name,
            'last_name' => $m->last_name,
            'suffix' => $m->suffix,
            'birthdate' => $m->birthdate,
            'sex' => $m->sex,
            'civil_status' => $m->civil_status,
        ])->save();
        return $r->id;
    }
}