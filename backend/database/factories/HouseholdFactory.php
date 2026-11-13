<?php

namespace Database\Factories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    private const STREETS = [
        'Rizal St.',
        'Mabini St.',
        'Bonifacio St.',
        'Del Pilar St.',
        'Luna St.',
        'Sampaguita St.',
        'Narra St.',
        'Mahogany St.',
        'Acacia St.',
        'Ilang-Ilang St.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $f = $this->faker;

        return [
            'purok' => 'Purok ' . $f->numberBetween(1, 7),
            'street_address' => $f->numberBetween(1, 250) . ' ' . $f->randomElement(self::STREETS),
            'housing_tenure' => $f->randomElement(['owned', 'rented', 'shared', 'informal_settler']),
            'house_material' => $f->randomElement(['concrete', 'semi_concrete', 'light_materials']),
            'water_source' => $f->randomElement(['piped', 'deep_well', 'spring', 'delivered', 'other']),
            'toilet_type' => $f->randomElement(['water_sealed', 'pit', 'shared', 'none']),
            'has_electricity' => $f->boolean(92),
            'income_bracket' => $f->randomElement(['below_10k', '10k_20k', '20k_40k', 'above_40k', null]),
            'is_4ps' => $f->boolean(15),
            'status' => 'pending',
            'consent_at' => now()->subDays($f->numberBetween(1, 180)),
        ];
    }
}
