<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;

class AssignmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Start fresh
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Assignment::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
