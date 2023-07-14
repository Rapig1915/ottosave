<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountUser;
use App\Models\NotificationPreferences;
use Illuminate\Support\Facades\DB;

class NotificationPreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        NotificationPreferences::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $seedAccountUsers = AccountUser::get();
        foreach ($seedAccountUsers as $seedAccountUser) {
            $notificationPreferences = new NotificationPreferences();
            $notificationPreferences->assignment_reminder_frequency = 'never';
            $notificationPreferences->defense_reminder_frequency = 'never';
            $notificationPreferences->account_user_id = $seedAccountUser->id;
            $notificationPreferences->save();
        }
    }
}
