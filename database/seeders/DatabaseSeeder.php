<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(OauthClientsSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(AccountsTableSeeder::class);
        $this->call(AccountInvitesSeeder::class);
        $this->call(InstitutionsSeeder::class);
        $this->call(InstitutionAccountsSeeder::class);
        $this->call(BankAccountsSeeder::class);
        $this->call(DemoAccountSeeder::class);
        $this->call(ScheduleItemsSeeder::class);
        $this->call(TransactionsSeeder::class);
        $this->call(AssignmentsSeeder::class);
        $this->call(DefenseSeeder::class);
        $this->call(NotificationPreferencesSeeder::class);
        $this->call(CouponSeeder::class);
    }
}
