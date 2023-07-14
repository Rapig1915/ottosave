<?php

namespace Database\Factories;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinicityOauthInstitution>
 */
class FinicityOauthInstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $faker = FakerFactory::create();
        return [
            'old_institution_id' => (string)$faker->numberBetween(1, 10000),
            'new_institution_id' => (string)$faker->numberBetween(1, 10000),
            'transition_message' => $faker->sentence(),
        ];
    }
}
