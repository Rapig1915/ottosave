<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $testUser = [
            'email' => 'testApp@buink.biz',
            'password' => 'buinkinc',
            'name' => 'Test User',
            'email_verified' => true
        ];

        $testBasicPlanUser = [
            'email' => 'testBasic@buink.biz',
            'password' => 'buinkinc',
            'name' => 'Test Basic User',
            'email_verified' => true
        ];

        $testAdminUser = [
            'email' => 'testAdmin@buink.biz',
            'password' => 'buinkinc',
            'name' => 'Test Admin User',
            'email_verified' => true
        ];

        $testDemoUser = [
            'email' => 'testDemo@buink.biz',
            'password' => 'buinkinc',
            'name' => 'Test Demo User',
            'email_verified' => true
        ];

        $testCoachUser = [
            'email' => 'coach+otto@buink.biz',
            'password' => 'buinkinc',
            'name' => 'Coach User',
            'email_verified' => true
        ];

        User::create($testUser);
        User::create($testBasicPlanUser);
        User::create($testAdminUser);
        User::create($testDemoUser);
        User::create($testCoachUser);
    }
}
