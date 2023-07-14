<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinicityRefresh>
 */
class FinicityRefreshFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'status' => 'complete',
            'error' => '',
            'finicity_refreshable_id' => Institution::factory(),
            'finicity_refreshable_type' => 'App\Models\Institution',
        ];
    }
}
