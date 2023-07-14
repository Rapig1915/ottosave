<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InstitutionsSeeder extends Seeder
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
        Institution::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $user = User::where('email', "testApp@buink.biz")->get()->first();

        // Institutions
        $seedInstitutions = [
            [
                'type' => '',
                'name' => 'Wells Fargo',
                'account_id' => $user->current_account->id,
            ],
        ];

        foreach ($seedInstitutions as $thisInstitution) {
            $institution = new Institution($thisInstitution);
            $institution->save();
        }
    }
}
