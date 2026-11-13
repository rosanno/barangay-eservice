<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class HouseholdSeeder extends Seeder
{
    private const TARGET_HOUSEHOLDS = 40;

    private Generator $faker;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->faker = fake('en_PH');

        Model::unguarded(fn() => DB::transaction(function () {
            // Reviewer for verified households (adjust role names to your RBAC)
            $reviewer = User::whereIn('role', ['admin', 'staff'])->first();

            // Resident accounts without a household (user_id is unique)
            $residents = User::where('role', 'resident')
                ->whereDoesntHave('household', fn($q) => $q->withTrashed())
                ->get();

            // Top up with new resident accounts if there aren't enough
            $missing = self::TARGET_HOUSEHOLDS - $residents->count();
            if ($missing > 0) {
                $residents = $residents->concat(
                    User::factory()->count($missing)->create(['role' => 'resident'])
                );
            }

            foreach ($residents->take(self::TARGET_HOUSEHOLDS) as $user) {
                $this->seedHousehold($user, $reviewer);
            }
        }));
    }

    private function seedHousehold(User $user, ?User $reviewer): void
    {
        $status = $this->faker->randomElement([
            'verified',
            'verified',
            'verified',
            'verified',
            'pending',
            'pending',
            'needs_update',
            'rejected',
            'inactive',
        ]);

        $attrs = ['user_id' => $user->id, 'status' => $status];

        if ($status === 'verified' && $reviewer) {
            $attrs['verified_by'] = $reviewer->id;
            $attrs['verified_at'] = now()->subDays($this->faker->numberBetween(1, 90));
        }

        $attrs['remarks'] = match ($status) {
            'needs_update' => 'Please update household member details and upload a valid ID.',
            'rejected' => 'Address could not be verified within the barangay.',
            default => null,
        };

        $household = Household::factory()->create($attrs);

        $this->seedMembers($household, $user);
    }

    private function seedMembers(Household $household, User $user): void
    {
        $f = $this->faker;

        $headSex = $f->randomElement(['male', 'female']);
        $headAge = $f->numberBetween(22, 70);
        $hasSpouse = $headAge >= 24 && $f->boolean(65);
        $lastName = $f->lastName();

        // Head
        $this->member($household, [
            'first_name' => $headSex === 'male' ? $f->firstNameMale() : $f->firstNameFemale(),
            'last_name' => $lastName,
            'relationship_to_head' => 'Head',
            'is_head' => true,
            'sex' => $headSex,
            'civil_status' => $hasSpouse ? 'married' : $f->randomElement(['single', 'widowed', 'separated', 'live_in']),
            'is_solo_parent' => !$hasSpouse && $f->boolean(12),
        ], $headAge);

        // Spouse
        if ($hasSpouse) {
            $spouseSex = $headSex === 'male' ? 'female' : 'male';
            $this->member($household, [
                'first_name' => $spouseSex === 'male' ? $f->firstNameMale() : $f->firstNameFemale(),
                'last_name' => $spouseSex === 'female' ? $lastName : $f->lastName(),
                'relationship_to_head' => 'Spouse',
                'sex' => $spouseSex,
                'civil_status' => 'married',
            ], max(20, $headAge + $f->numberBetween(-5, 5)));
        }

        // Children
        $maxChildAge = max(0, min($headAge - 18, 30));
        foreach (range(1, $f->numberBetween(0, 4)) as $i) {
            if ($maxChildAge < 1)
                break;
            $sex = $f->randomElement(['male', 'female']);
            $age = $f->numberBetween(0, $maxChildAge);
            $this->member($household, [
                'first_name' => $sex === 'male' ? $f->firstNameMale() : $f->firstNameFemale(),
                'last_name' => $lastName,
                'relationship_to_head' => $sex === 'male' ? 'Son' : 'Daughter',
                'sex' => $sex,
                'civil_status' => 'single',
            ], $age);
        }

        // Other relative
        if ($f->boolean(20)) {
            $sex = $f->randomElement(['male', 'female']);
            $this->member($household, [
                'first_name' => $sex === 'male' ? $f->firstNameMale() : $f->firstNameFemale(),
                'last_name' => $f->lastName(),
                'relationship_to_head' => $f->randomElement(['Parent', 'Sibling', 'Grandchild', 'Other relative']),
                'sex' => $sex,
                'civil_status' => $f->randomElement(['single', 'married', 'widowed']),
            ], $f->numberBetween(18, 80));
        }
    }

    private function member(Household $household, array $data, int $age): void
    {
        $f = $this->faker;

        $birthdate = Carbon::today()
            ->subYears($age)
            ->subDays($f->numberBetween(0, 364));

        $isAdult = $age >= 18;

        HouseholdMember::create(array_merge([
            'household_id' => $household->id,
            'middle_name' => $isAdult || $f->boolean(60) ? $f->lastName() : null,
            'suffix' => $f->boolean(4) ? $f->randomElement(['Jr.', 'Sr.', 'III']) : null,
            'is_head' => false,
            'birthdate' => $birthdate->toDateString(),
            'education' => $this->education($age),
            'occupation' => $isAdult ? $f->randomElement([
                'Farmer',
                'Fisherman',
                'Vendor',
                'Driver',
                'Construction Worker',
                'Teacher',
                'Government Employee',
                'OFW',
                'Housewife',
                'Unemployed',
            ]) : ($age >= 6 ? 'Student' : null),
            'is_pwd' => $f->boolean(4),
            'is_solo_parent' => false,
            'is_registered_voter' => $isAdult && $f->boolean(85),
        ], $data));
    }

    private function education(int $age): ?string
    {
        if ($age < 6)
            return null;
        if ($age < 12)
            return 'Elementary';
        if ($age < 16)
            return 'Junior High School';
        if ($age < 18)
            return 'Senior High School';

        return $this->faker->randomElement([
            'Elementary',
            'High School Graduate',
            'Senior High School',
            'Vocational',
            'College Level',
            'College Graduate',
            'Post-graduate',
        ]);
    }
}
