<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\InstitutionAccount;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
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
            'name' => $faker->name(),
            'account_id' => Account::factory(),
            'slug' => null,
            'type' => 'savings',
            'color' => 'purple',
            'icon' => 'square',
            'parent_bank_account_id' => null,
            'sub_account_order' => 1,
            'online_banking_url' => $faker->url(),
            'balance_current' => $faker->randomFloat(2, 0, 10000),
            'institution_account_id' => InstitutionAccount::factory(),
        ];
    }
}
