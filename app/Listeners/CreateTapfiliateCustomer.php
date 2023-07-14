<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\AccountCreated;
use App\Services\External\TapfiliateService;

class CreateTapfiliateCustomer implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    /**
     * Register referred user with Tapfiliate
     *
     * @param  AccountCreated  $event
     * @return void
     */
    public function handle(AccountCreated $event)
    {
        if ($event->referralCode) {
            try {
                $tapfiliateService = new TapfiliateService();
                $tapfiliateService->createCustomer($event->referralCode, $event->account->id);
            } catch (\Exception $e) {
                report($e);
            }
        }
    }
}
