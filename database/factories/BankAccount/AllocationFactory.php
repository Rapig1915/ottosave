<?php

namespace Database\Factories\BankAccount;

use App\Models\BankAccount;
use App\Models\Defense;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount\Allocation>
 */
class AllocationFactory extends Factory
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
            'defense_id' => Defense::factory(),
            'bank_account_id' => BankAccount::factory(),
            'transferred_from_id' => BankAccount::factory(),
            'amount' => $faker->randomFloat(2, 0, 200),
            'transferred' => 0,
            'cleared' => 0,
            'cleared_out' => 0,
        ];
    }
}
