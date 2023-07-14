<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\User;
use App\Models\AccountUser;

class DemoAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Remove Dispatcher to prevent model events from firing during the seed process
        Account::unsetEventDispatcher();
        AccountUser::unsetEventDispatcher();

        $demoUsers = User::whereIn('email', ["testDemo@buink.biz", "coach+otto@buink.biz"])->get();
        foreach($demoUsers as $demoUser){
            $demoUser->current_account->initializeForDemo();
        }

        // Add Dispatcher
        Account::setEventDispatcher(new \Illuminate\Events\Dispatcher);
        AccountUser::setEventDispatcher(new \Illuminate\Events\Dispatcher);
    }
}
