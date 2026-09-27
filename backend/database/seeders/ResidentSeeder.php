<?php

namespace Database\Seeders;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResidentSeeder extends Seeder
{
    private const MALE_FIRST_NAMES = [
        'Juan',
        'Jose',
        'Carlos',
        'Miguel',
        'Antonio',
        'Ramon',
        'Eduardo',
        'Ricardo',
        'Fernando',
        'Rafael',
        'Roberto',
        'Manuel',
        'Francisco',
        'Alfredo',
        'Ernesto',
        'Leonardo',
        'Marco',
        'Gabriel',
        'Vicente',
        'Emilio',
        'Domingo',
        'Andres',
    ];

    private const FEMALE_FIRST_NAMES = [
        'Maria',
        'Ana',
        'Rosa',
        'Carmen',
        'Teresa',
        'Elena',
        'Cristina',
        'Patricia',
        'Andrea',
        'Isabel',
        'Gloria',
        'Luz',
        'Corazon',
        'Josefina',
        'Remedios',
        'Angelica',
        'Beatriz',
        'Diana',
        'Estrella',
        'Fe',
        'Consuelo',
        'Perla',
    ];

    private const LAST_NAMES = [
        'Dela Cruz',
        'Santos',
        'Reyes',
        'Garcia',
        'Ramos',
        'Mendoza',
        'Torres',
        'Flores',
        'Villanueva',
        'Bautista',
        'Aquino',
        'Castillo',
        'Del Rosario',
        'Gonzales',
        'Pascual',
        'Salazar',
        'Fernandez',
        'Rivera',
        'Lopez',
        'Domingo',
        'Aguilar',
        'Marquez',
    ];

    private const OCCUPATIONS = [
        'Farmer',
        'Fisherman',
        'Vendor',
        'Teacher',
        'Driver',
        'Carpenter',
        'Housewife',
        'Government Employee',
        'Nurse',
        'Store Owner',
        'Laborer',
        'Electrician',
        'Mechanic',
        'Security Guard',
        'Unemployed',
        'OFW',
    ];

    private const RELIGIONS = [
        'Roman Catholic',
        'Iglesia ni Cristo',
        'Born Again Christian',
        'Islam',
        'Seventh-day Adventist',
        'Baptist',
        'None',
    ];

    private const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private const PUROKS = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'];

    private const PLACES_OF_BIRTH = [
        'Manila',
        'Quezon City',
        'Cebu City',
        'Davao City',
        'Iloilo City',
        'Baguio City',
        'Cagayan de Oro',
        'Zamboanga City',
        'Bacolod City',
        'General Santos',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            for ($i = 1; $i <= 50; $i++) {
                $this->createResident($i);
            }
        });

        $this->command?->info('Seeded 50 residents. Every account password is: password');
    }

    private function createResident(int $index): void
    {
        $sex = fake()->randomElement(['male', 'female']);
        $firstName = $sex === 'male'
            ? fake()->randomElement(self::MALE_FIRST_NAMES)
            : fake()->randomElement(self::FEMALE_FIRST_NAMES);
        $middleName = fake()->randomElement(self::LAST_NAMES);
        $lastName = fake()->randomElement(self::LAST_NAMES);

        $dateOfBirth = Carbon::now()->subYears(fake()->numberBetween(18, 75))
            ->subDays(fake()->numberBetween(0, 365));

        $isMarried = $dateOfBirth->age >= 22 && fake()->boolean(55);

        $fullName = trim("{$firstName} {$middleName} {$lastName}");

        // Guaranteed-unique email regardless of how many times this seeder
        // runs against the same database — index avoids collisions that
        // fake()->unique() can eventually exhaust across large runs.
        $email = Str::slug("{$firstName}.{$lastName}", '.') . ".{$index}@example.com";

        $user = User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'resident',
        ]);

        Resident::create([
            'user_id' => $user->id,
            'purok' => fake()->randomElement(self::PUROKS),
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'sex' => $sex,
            'date_of_birth' => $dateOfBirth->toDateString(),
            'place_of_birth' => fake()->randomElement(self::PLACES_OF_BIRTH),
            'citizenship' => 'Filipino',
            'religion' => fake()->randomElement(self::RELIGIONS),
            'blood_type' => fake()->optional(0.7)->randomElement(self::BLOOD_TYPES),

            'mother_first_name' => fake()->randomElement(self::FEMALE_FIRST_NAMES),
            'mother_middle_name' => fake()->randomElement(self::LAST_NAMES),
            'mother_last_name' => fake()->randomElement(self::LAST_NAMES),
            'mother_occupation' => fake()->optional(0.8)->randomElement(self::OCCUPATIONS),

            'father_first_name' => fake()->randomElement(self::MALE_FIRST_NAMES),
            'father_middle_name' => fake()->randomElement(self::LAST_NAMES),
            'father_last_name' => $lastName, // commonly shared with the resident's own surname
            'father_suffix' => fake()->optional(0.15)->randomElement(['Jr.', 'Sr.', 'III']),
            'father_occupation' => fake()->optional(0.8)->randomElement(self::OCCUPATIONS),

            'spouse_first_name' => $isMarried
                ? fake()->randomElement($sex === 'male' ? self::FEMALE_FIRST_NAMES : self::MALE_FIRST_NAMES)
                : null,
            'spouse_middle_name' => $isMarried ? fake()->randomElement(self::LAST_NAMES) : null,
            'spouse_last_name' => $isMarried ? $lastName : null,
            'spouse_suffix' => null,
            'number_of_children' => $isMarried ? fake()->numberBetween(0, 5) : null,

            'emergency_contact_first_name' => fake()->randomElement([...self::MALE_FIRST_NAMES, ...self::FEMALE_FIRST_NAMES]),
            'emergency_contact_middle_name' => fake()->randomElement(self::LAST_NAMES),
            'emergency_contact_last_name' => fake()->randomElement(self::LAST_NAMES),
            'emergency_contact_suffix' => null,
            'emergency_contact_number' => '09' . fake()->numerify('#########'),
        ]);
    }
}