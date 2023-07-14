<?php

namespace Database\Factories;

use App\Models\Account;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Defense>
 */
class DefenseFactory extends Factory
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
            'account_id' => function() {
                return Account::factory()->create()->id;
            },
            'end_date' => $faker->dateTimeBetween('+1 months', '+2 months'),
            'allocated' => false,
            'spending_account_starting_balance' => $faker->randomFloat(2, 10, 1000),
            'balance_snapshots' => [1 => $faker->randomFloat(2, 10, 1000)],
        ];
    }
}
