<?php

namespace App\Console\Commands;

use App\Models\AccountUser;
use Illuminate\Console\Command;

class AssignUserRoleOwner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'buink:assign:user-role:owner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign owner role on existing users in the system';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line("[buink:assign:user-role:owner] Started");

        $countRoleAssigned = 0;
        
        AccountUser::chunk(2, function ($accountUsers) use(&$countRoleAssigned){
            foreach($accountUsers as $accountUser){
                $hasOwnerOrCoachRole = $accountUser->hasRole('owner') || $accountUser->hasRole('coach');
                if($hasOwnerOrCoachRole){
                    continue;
                }
                
                $accountUser->assignRole('owner');
                $countRoleAssigned ++;
            }
        });
        
        $this->line("[buink:assign:user-role:owner] Assigned owner role to {$countRoleAssigned} users");
        $this->line("[buink:assign:user-role:owner] Finished");
        return 0;
    }
}
