<?php

namespace Database\Factories;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
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
            'type_slug' => 'gift_code',
            'reward_type' => 'free_month',
            'amount' => $faker->numberBetween(1, 100),
            'code' => $faker->md5(),
            'expiration_date' => $faker->dateTimeBetween('now', '+30 years'),
            'number_of_uses' => 1,
            'reward_duration_in_months' => 1
        ];
    }
}
