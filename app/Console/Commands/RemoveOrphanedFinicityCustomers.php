<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Storage;

class RemoveOrphanedFinicityCustomers extends Command
{
    protected $signature = 'dym:remove-orphan-finicity-customers {--checkonly=} {--code==}';
    protected $description = 'Delete finicity customers of either downgraded or deleted users';

    private $code;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if($this->option('code')){
            $this->code = $this->option('code');
        }

        $optionCheckOnly = $this->option('checkonly');
        if(!in_array($optionCheckOnly, ['yes', 'no'])){
            $this->log("Required option [checkonly] should be either 'yes' or 'no'", 'warn');
            $this->log("Example Usage: php artisan dym:remove-idle-finicity-customers --checkonly=yes");
            return;
        }

        $isCheckOnly = $optionCheckOnly !== "no";

        $this->log("Started");
        if($isCheckOnly){
            $this->log("Check-Only mode...");
        }

        Account::with('accountUsers')->chunk(10, function ($accounts) use($isCheckOnly){
            foreach($accounts as $account){
                $finicityCustomerExist = !is_null($account->finicity_customer);
                if($finicityCustomerExist){
                    $hasOwnerUser = $account->accountUsers->contains(function ($accountUser) {
                        return $accountUser->hasRole('owner');
                    });
                    $isAccountActive = in_array($account->status, ['active', 'grace', 'free_trial', 'trial_grace', 'pending_renewal']);
                    $accountDowngraded = $account->subscription_plan === 'basic';
                    
                    $shouldDeleteFinicityCustomer = !$hasOwnerUser || !$isAccountActive || $accountDowngraded;

                    if($shouldDeleteFinicityCustomer){
                        $deleteReason = '';
                        if(!$hasOwnerUser){
                            $deleteReason = 'User deleted';
                        } else if(!$isAccountActive){
                            $deleteReason = 'Account is not active';
                        } else if($accountDowngraded){
                            $deleteReason = 'Account is downgraded';
                        }

                        $this->log("Account #{$account->id} to delete finicity customer. Reason: $deleteReason");
                        if(!$isCheckOnly){
                            $account->deleteFinicityCustomer();
                        }
                    }
                }
            }
        });

        $this->log("Finished");
    }

    private function log($message, $type = 'info'){
        $time = date('Y-m-d H:i:s');
        $signature = explode(' ', $this->signature)[0] ?? '';
        $composedMessage = "[$time][$type][$signature] $message";

        if($type === 'error'){
            $this->error($composedMessage);
        } else if($type === 'warn'){
            $this->warn($composedMessage);
        } else if($type === 'info') {
            $this->info($composedMessage);
        } else {
            $this->line($composedMessage);
        }

        $logOnFile = !empty($this->code);
        if($logOnFile){
            $path = "logs/commands/{$this->code}.log";
            $time = date('Y-m-d H:i:s');
            $signature = explode(' ', $this->signature)[0] ?? '';
            Storage::disk('local')->append($path, $composedMessage);
        }
    }
}
