<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Defense;
use App\Models\BankAccount\Allocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DefenseSeeder extends Seeder
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
        Defense::truncate();
        Allocation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $user = User::where('email', "testApp@buink.biz")->get()->first();
        $account = $user->current_account;

        $account->getCurrentDefense();
    }
}
