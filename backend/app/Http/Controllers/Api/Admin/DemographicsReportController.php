<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemographicsReportController extends Controller
{
    private const AGE = 'TIMESTAMPDIFF(YEAR, hm.birthdate, CURDATE())';

    private const AGE_GROUPS = ['0-4', '5-14', '15-24', '25-34', '35-44', '45-59', '60+'];

    private const EDUCATION_ORDER = [
        'Elementary',
        'Junior High School',
        'Senior High School',
        'High School Graduate',
        'Vocational',
        'College Level',
        'College Graduate',
        'Post-graduate',
        'Not specified',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $purok = $request->query('purok');
        $status = $request->query('status', 'verified'); // verified | all
        $age = self::AGE;

        // Fresh base query each time (members of non-deleted households)
        $members = fn() => DB::table('household_members as hm')
            ->join('households as h', 'h.id', '=', 'hm.household_id')
            ->whereNull('h.deleted_at')
            ->when($status !== 'all', fn($q) => $q->where('h.status', 'verified'))
            ->when($purok, fn($q) => $q->where('h.purok', $purok));

        // ---- Summary
        $totalPopulation = $members()->count();
        $totalHouseholds = $members()->distinct()->count('h.id');

        // ---- Age groups
        $bucket = "CASE
            WHEN {$age} < 5  THEN '0-4'
            WHEN {$age} < 15 THEN '5-14'
            WHEN {$age} < 25 THEN '15-24'
            WHEN {$age} < 35 THEN '25-34'
            WHEN {$age} < 45 THEN '35-44'
            WHEN {$age} < 60 THEN '45-59'
            ELSE '60+' END";
        $ageRows = $members()->selectRaw("$bucket as label, COUNT(*) as total")
            ->groupBy('label')->pluck('total', 'label');

        // ---- By purok
        $purokRows = $members()
            ->selectRaw('h.purok as label, COUNT(*) as population, COUNT(DISTINCT h.id) as households')
            ->groupBy('h.purok')
            ->orderByRaw("CAST(SUBSTRING_INDEX(h.purok, ' ', -1) AS UNSIGNED), h.purok")
            ->get();

        // ---- Sex distribution
        $sexRows = $members()->selectRaw('hm.sex as label, COUNT(*) as total')
            ->groupBy('hm.sex')->pluck('total', 'label');

        // ---- Employment (working-age, 15+)
        $employmentCase = "CASE
            WHEN hm.occupation IS NULL OR hm.occupation = '' THEN 'No occupation recorded'
            WHEN LOWER(hm.occupation) = 'unemployed' THEN 'Unemployed'
            WHEN LOWER(hm.occupation) = 'student' THEN 'Student'
            WHEN LOWER(hm.occupation) IN ('housewife', 'homemaker') THEN 'Homemaker'
            ELSE 'Employed' END";
        $employmentRows = $members()->whereRaw("{$age} >= 15")
            ->selectRaw("$employmentCase as label, COUNT(*) as total")
            ->groupBy('label')->pluck('total', 'label');

        $employed = (int) ($employmentRows['Employed'] ?? 0);
        $unemployed = (int) ($employmentRows['Unemployed'] ?? 0);
        $laborForce = $employed + $unemployed;

        $topOccupations = $members()->whereRaw("{$age} >= 15")
            ->whereNotNull('hm.occupation')->where('hm.occupation', '!=', '')
            ->whereNotIn(DB::raw('LOWER(hm.occupation)'), ['unemployed', 'student', 'housewife', 'homemaker'])
            ->selectRaw('hm.occupation as label, COUNT(*) as total')
            ->groupBy('hm.occupation')->orderByDesc('total')->limit(8)->get();

        // ---- Educational attainment
        $educationRows = $members()
            ->selectRaw("COALESCE(NULLIF(hm.education, ''), 'Not specified') as label, COUNT(*) as total")
            ->groupBy('label')->pluck('total', 'label');

        // ---- Household size
        $sizeRows = DB::query()
            ->fromSub($members()->selectRaw('h.id, COUNT(*) as size')->groupBy('h.id'), 's')
            ->selectRaw("CASE WHEN size >= 7 THEN '7+' ELSE CAST(size AS CHAR) END as label, COUNT(*) as total")
            ->groupBy('label')->pluck('total', 'label');

        // ---- Seniors & PWD
        $sector = $members()->selectRaw("
            COALESCE(SUM({$age} >= 60), 0) as seniors,
            COALESCE(SUM({$age} >= 60 AND hm.sex = 'male'), 0) as seniors_male,
            COALESCE(SUM({$age} >= 60 AND hm.sex = 'female'), 0) as seniors_female,
            COALESCE(SUM(hm.is_pwd = 1), 0) as pwd,
            COALESCE(SUM({$age} >= 60 AND hm.is_pwd = 1), 0) as senior_pwd
        ")->first();

        return response()->json([
            'data' => [
                'summary' => [
                    'population' => $totalPopulation,
                    'households' => $totalHouseholds,
                    'avg_household_size' => $totalHouseholds ? round($totalPopulation / $totalHouseholds, 1) : 0,
                ],
                'age_groups' => $this->fill(self::AGE_GROUPS, $ageRows),
                'purok' => $purokRows,
                'sex' => [
                    'male' => (int) ($sexRows['male'] ?? 0),
                    'female' => (int) ($sexRows['female'] ?? 0),
                ],
                'employment' => [
                    'breakdown' => $this->fill(
                        ['Employed', 'Unemployed', 'Student', 'Homemaker', 'No occupation recorded'],
                        $employmentRows
                    ),
                    'employment_rate' => $laborForce ? round($employed / $laborForce * 100, 1) : 0,
                    'top_occupations' => $topOccupations,
                ],
                'education' => $this->fill(self::EDUCATION_ORDER, $educationRows),
                'household_size' => $this->fill(['1', '2', '3', '4', '5', '6', '7+'], $sizeRows),
                'sectors' => [
                    'seniors' => (int) $sector->seniors,
                    'seniors_male' => (int) $sector->seniors_male,
                    'seniors_female' => (int) $sector->seniors_female,
                    'pwd' => (int) $sector->pwd,
                    'senior_pwd' => (int) $sector->senior_pwd,
                ],
                'filters' => [
                    'puroks' => DB::table('households')->whereNull('deleted_at')
                        ->distinct()->orderBy('purok')->pluck('purok'),
                ],
            ]
        ]);
    }

    /** Return [{label, total}] in a fixed order, with zeros for missing labels. */
    private function fill(array $labels, Collection $rows): array
    {
        return collect($labels)
            ->map(fn($l) => ['label' => $l, 'total' => (int) ($rows[$l] ?? 0)])
            ->all();
    }
}