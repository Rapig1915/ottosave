<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\UserLogin' => [
            'App\Listeners\RefreshAccountBalances',
        ],
        'App\Events\AllocationTransferred' => [
            'App\Listeners\ClearPayoffAssignments',
        ],
        'App\Events\AccountCreated' => [
            'App\Listeners\CreateTapfiliateCustomer',
        ],
        'App\Events\BraintreeRenewalComplete' => [
            'App\Listeners\CreateTapfiliateConversion',
        ],
        'App\Events\ITunesRenewalComplete' => [
            'App\Listeners\CreateTapfiliateConversion',
        ],
    ];

    protected $subscribe = [
        'App\Listeners\UserListEventSubscriber',
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
