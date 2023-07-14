<?php

namespace Database\Factories;

use App\Models\Institution;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InstitutionAccount>
 */
class InstitutionAccountFactory extends Factory
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
            'institution_id' => function() {
                return Institution::factory()->create()->id;
            },
            'name' => $faker->company(),
            'official_name' => $faker->creditCardType(),
            'remote_id' => $faker->sha1(),
            'balance_available' => $faker->randomFloat(2),
            'balance_current' => $faker->randomFloat(2),
            'balance_limit' => $faker->randomFloat(2),
            'iso_currency_code' => 'USD',
            'mask' => (string)$faker->randomNumber(4),
            'subtype' => 'checking',
            'linked_at' => $faker->dateTime(),
            'api_status_message' => '',
            'remote_status_code' => '123'
        ];
    }
}
