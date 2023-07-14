<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\User;
use App\Models\AccountUser;
use Illuminate\Support\Facades\DB;

class AccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Account::truncate();
        AccountUser::truncate();
        DB::statement('TRUNCATE account_user_role');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // Remove Dispatcher to prevent model events from firing during the seed process
        Account::unsetEventDispatcher();
        AccountUser::unsetEventDispatcher();

        $plusExpirationDate = new \DateTime();
        $plusExpirationDate->add(new \DateInterval('P1M'));

        $seedAccounts = [
            [
                'subscription_plan' => 'plus',
                'subscription_type' => 'monthly',
                'user_email' => 'testApp@buink.biz',
                'status' => 'free_trial',
                'expire_date' => $plusExpirationDate,
                'coach_emails' => [
                    'coach+otto@buink.biz'
                ]
            ],
            [
                'subscription_plan' => 'plus',
                'subscription_type' => 'monthly',
                'user_email' => 'testAdmin@buink.biz',
                'status' => 'active',
                'expire_date' => $plusExpirationDate,
            ],
            [
                'subscription_plan' => 'basic',
                'subscription_type' => 'monthly',
                'user_email' => 'testBasic@buink.biz',
                'status' => 'active',
                'expire_date' => new \DateTime(),
                'coach_emails' => [
                    'coach+otto@buink.biz'
                ]
            ],
            [
                'subscription_plan' => 'basic',
                'subscription_type' => 'monthly',
                'user_email' => 'testDemo@buink.biz',
                'status' => 'demo',
                'expire_date' => new \DateTime()
            ],
            [
                'subscription_plan' => 'basic',
                'subscription_type' => 'monthly',
                'user_email' => 'coach+otto@buink.biz',
                'status' => 'demo',
                'expire_date' => new \DateTime()
            ]
        ];

        foreach ($seedAccounts as $seedAccountData) {
            $account = new Account;
            $account->status = $seedAccountData['status'];
            $account->subscription_plan = $seedAccountData['subscription_plan'];
            $account->subscription_type = $seedAccountData['subscription_type'];
            $account->expire_date = $seedAccountData['expire_date'];

            $seedUserOwner = User::where('email', $seedAccountData['user_email'])->first();
            if($seedUserOwner) {
                $seedAccountOwner = $seedUserOwner->accounts()->save($account);
                if($seedAccountOwner) {
                    $seedAccountUser = $seedUserOwner->accountUsers()->where('account_id', $seedAccountOwner->id)->first();
                    if(!$seedAccountUser) {
                        continue;
                    }
                    
                    $roles = ['owner'];
                    if ($seedUserOwner->email === "testAdmin@buink.biz") {
                        $roles[] = 'super-admin';
                    }
        
                    $seedAccountUser->assignRole($roles);
                }
            }

            $seedCoachEmails = $seedAccountData['coach_emails'] ?? [];
            foreach($seedCoachEmails as $seedCoachEmail) {
                $seedUserCoach = User::where('email', $seedCoachEmail)->first();
                if($seedUserCoach) {
                    $seedAccountCoach = $seedUserCoach->accounts()->save($account);
                    if($seedAccountCoach) {
                        $seedAccountUser = $seedUserCoach->accountUsers()->where('account_id', $seedAccountCoach->id)->first();
                        if(!$seedAccountUser) {
                            continue;
                        }
                        
                        $roles = ['coach'];
                        $seedAccountUser->assignRole($roles);
                    }
                }
            }
        }

        // Add Dispatcher
        Account::setEventDispatcher(new \Illuminate\Events\Dispatcher);
        AccountUser::setEventDispatcher(new \Illuminate\Events\Dispatcher);
    }
}
