<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
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
            'bank_account_id' => BankAccount::factory(),
            'amount' => $faker->randomFloat(2),
            'merchant' => $faker->company(),
            'remote_merchant' => $faker->company(),
            'action_type' => 'digital',
            'remote_transaction_id' => $faker->md5(),
            'remote_transaction_date' => $faker->dateTimeBetween('-2 months', '-1 months'),
            'remote_account_id' => $faker->md5(),
            'remote_category' => $faker->word(),
            'remote_category_id' => $faker->md5(),
            'is_assignable' => true,
            'allocation_id' => null
        ];
    }
}
