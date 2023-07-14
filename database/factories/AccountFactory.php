<?php

namespace Database\Factories;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
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
            'status' => 'active',
            'expire_date' => $faker->dateTimeBetween('+1 years', '+2 years'),
            'subscription_plan' => 'plus',
            'projected_defenses_per_month' => 1,
        ];
    }
}
