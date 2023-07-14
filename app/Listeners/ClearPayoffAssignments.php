<?php

namespace App\Listeners;

use App\Events\AllocationTransferred;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use \Carbon\Carbon;
use App\Models\Account;
use App\Models\BankAccount\Allocation;

class ClearPayoffAssignments
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
    public function handle(AllocationTransferred $event)
    {
        $allocation = $event->allocation;
        $allocation->load(
            'bankAccount',
            'bankAccount.account',
            'bankAccount.account.payoffAccount'
        );
        $payoffAccount = $event->allocation->bankAccount->account->payoffAccount;
        $payoffAccount->untransferredAssignments()->update(['transferred' => true]);
    }
}
