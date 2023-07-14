<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InstitutionAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InstitutionAccountsSeeder extends Seeder
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
        InstitutionAccount::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get relational info
        $user = User::where('email', "testApp@buink.biz")->get()->first();
        $institution = $user->current_account->institutions()->first();

        // Institution Accounts
        $seedInstitutionAccounts = [
            [
                'id' => 1,
                'institution_id' => $institution->id,
                'name' => 'Wells Fargo Checking',
                'remote_id' => '1234',
                'balance_available' => '100',
                'balance_current' => '110',
                'balance_limit' => null,
                'mask' => 3333,
                'subtype' => '',
                'iso_currency_code' => 'USD',
            ],
        ];

        foreach( $seedInstitutionAccounts AS $thisInstitutionAccount ) {
            $institutionAccount = new InstitutionAccount( $thisInstitutionAccount );
            $institutionAccount->save();
        }
    }
}
