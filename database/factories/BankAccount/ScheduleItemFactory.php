<?php

namespace Database\Factories\BankAccount;

use App\Models\BankAccount;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAcocunt\ScheduleItem>
 */
class ScheduleItemFactory extends Factory
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
            'bank_account_id' => function() {
                return BankAccount::factory()->create()->id;
            },
            'description' => $faker->sentence(4),
            'amount_total' => $faker->randomFloat(2),
            'amount_monthly' => $faker->randomFloat(2),
            'type' => $faker->randomElement(['monthly', 'quarterly', 'yearly', 'target_date']),
            'date_end' => $faker->dateTimeBetween('+1 years', '+2 years'),
            'approximate_due_date' => 'January'
        ];
    }
}
