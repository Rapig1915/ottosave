<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\AccountInvite;
use App\Models\User;
use App\Models\AccountUser;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class AccountInvitesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('TRUNCATE account_invites');
        DB::statement('TRUNCATE account_invite_role');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // Remove Dispatcher to prevent model events from firing during the seed process
        AccountInvite::unsetEventDispatcher();

        $seedAccountInvites = [
            [
                'owner_email' => 'testApp@buink.biz',
                'name' => 'Jackson',
                'email' => 'testInviteeApp@buink.biz',
                'role' => ['coach'],
                'accepted' => false,
            ]
        ];

        foreach ($seedAccountInvites as $seedAccountInviteData) {
            $user = User::where('email', $seedAccountInviteData['owner_email'])->first();
            if(!$user){
                continue;
            }
            
            $accountUserOwner = $user->accountUsers()->whereHas('roles', function($query){
                $query->where('name', 'owner');
            })->first();
            if(!$accountUserOwner){
                continue;
            }

            $account = $accountUserOwner->account;
            if(!$account){
                continue;
            }

            $name = $seedAccountInviteData['name'];
            $email = $seedAccountInviteData['email'];
            $roleNames = $seedAccountInviteData['role'];
            $isAccepted = $seedAccountInviteData['accepted'];

            $roleIds = Role::whereIn('name', $roleNames)->select('id')->get()->pluck('id')->all();
            $accountInvite = AccountInvite::inviteUserToAccount($account->id, $name, $email, $roleIds);
            if($isAccepted){
                $accountInvite->update([
                    'status' => 'accepted'
                ]);
            }
        }

        // Add Dispatcher
        AccountInvite::setEventDispatcher(new \Illuminate\Events\Dispatcher);
    }
}
