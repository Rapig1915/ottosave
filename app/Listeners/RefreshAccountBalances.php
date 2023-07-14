<?php

namespace App\Listeners;

use App\Events\UserLogin;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use \Carbon\Carbon;
use App\Jobs\UpdateTransactionsByInstitutionJob;
use App\Jobs\InstitutionAccountBalanceJob;
use App\Jobs\RefreshFinicityAccountsJob;

class RefreshAccountBalances
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserLogin  $event
     * @return void
     */
    public function handle(UserLogin $event)
    {
        $user = $event->user;
        $user->loadMissing(
            'accounts',
            'accounts.finicity_customer'
        );
        foreach ($user->accounts as $account) {
            $isPlusSubscriber = $account->subscription_plan === 'plus';
            if ($isPlusSubscriber) {
                $this->refreshBankAccounts($account);
            }
        }
    }

    private function refreshBankAccounts($account)
    {
        if ($account->finicity_customer) {
            $account->finicity_customer->getOrCreatePendingFinicityRefresh();
            RefreshFinicityAccountsJob::dispatch($account->finicity_customer);
        }
    }
}
