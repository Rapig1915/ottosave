<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use \App\Mail\Notifications\UserListUpdateReport;
use App\Events\AccountCreated;
use App\Events\AccountDowngraded;
use App\Events\AccountStatusUpdated;
use App\Events\AccountSubscriptionPlanUpdated;
use App\Events\InstitutionCreated;
use App\Events\InstitutionAccountCountChanged;
use App\Events\UserDeleted;
use App\Jobs\SyncUserToActiveCampaignJob;
use Carbon\Carbon;
use App\Services\SubscriptionService;

class UserListEventSubscriber
{
    public function __construct()
    {
        //
    }

    public function subscribe($events)
    {
        $events->listen(
            'App\Events\AccountCreated',
            'App\Listeners\UserListEventSubscriber@handleAccountCreatedEvent'
        );
        $events->listen(
            'App\Events\AccountStatusUpdated',
            'App\Listeners\UserListEventSubscriber@handleAccountStatusUpdatedEvent'
        );
        $events->listen(
            'App\Events\AccountSubscriptionPlanUpdated',
            'App\Listeners\UserListEventSubscriber@handleAccountSubscriptionPlanUpdatedEvent'
        );
        $events->listen(
            'App\Events\InstitutionCreated',
            'App\Listeners\UserListEventSubscriber@handleInstitutionCreatedEvent'
        );
        $events->listen(
            'App\Events\InstitutionAccountCountChanged',
            'App\Listeners\UserListEventSubscriber@handleInstitutionAccountCountChangedEvent'
        );
        $events->listen(
            'App\Events\UserDeleted',
            'App\Listeners\UserListEventSubscriber@handleUserDeletedEvent'
        );
        $events->listen(
            'App\Events\AccountDowngraded',
            'App\Listeners\UserListEventSubscriber@handleAccountDowngradedEvent'
        );
    }

    public function handleAccountCreatedEvent(AccountCreated $event)
    {
        $supportEmailAddress = config('mail.from.address');
        Mail::to($supportEmailAddress)->queue(new UserListUpdateReport($event->account, 'New User Added'));

        // Reinstate demo account upon creation
        //  Disabled for now
        // $event->account->initializeForDemo();

        // Start trial membership
        $event->account->createRequiredBankAccounts();
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        SubscriptionService::initializeTrialSubscription($event->account, $thirtyDaysFromNow);
        $event->account->save();
    }

    public function handleAccountStatusUpdatedEvent(AccountStatusUpdated $event)
    {
        $supportEmailAddress = config('mail.from.address');
        $isStandardRenewalEvent = $event->oldStatus === 'active' && $event->newStatus === 'pending_renewal';
        $isSuccessfulStandardRenewal = $event->oldStatus === 'pending_renewal' && $event->newStatus === 'active';
        if (!$isStandardRenewalEvent && !$isSuccessfulStandardRenewal) {
            Mail::to($supportEmailAddress)->queue(new UserListUpdateReport($event->account, 'Account Status Updated', $event->oldStatus, $event->newStatus));
            foreach ($event->account->users as $user) {
                SyncUserToActiveCampaignJob::dispatch('sync_details', ['user' => $user]);
            }
        }
    }
    public function handleAccountSubscriptionPlanUpdatedEvent(AccountSubscriptionPlanUpdated $event)
    {
        $supportEmailAddress = config('mail.from.address');
        Mail::to($supportEmailAddress)->queue(new UserListUpdateReport($event->account, 'Account Subscription Plan Updated', $event->oldPlan, $event->newPlan));
        foreach ($event->account->users as $user) {
            SyncUserToActiveCampaignJob::dispatch('sync_details', ['user' => $user]);
        }
    }
    public function handleInstitutionCreatedEvent(InstitutionCreated $event)
    {
        $supportEmailAddress = config('mail.from.address');
        Mail::to($supportEmailAddress)->queue(new UserListUpdateReport($event->account, 'Institution Added'));
    }
    public function handleInstitutionAccountCountChangedEvent(InstitutionAccountCountChanged $event)
    {
        foreach ($event->account->users as $user) {
            SyncUserToActiveCampaignJob::dispatch('sync_details', ['user' => $user]);
        }
    }
    public function handleUserDeletedEvent(UserDeleted $event)
    {
        $accountUserOwner = $event->user->accountUsers()->whereHas('roles', function($query){
            $query->where('name', 'owner');
        })->first();
        if($accountUserOwner){
            $account = $accountUserOwner->account;
            $account->deleteFinicityCustomer();
        }
    }
    public function handleAccountDowngradedEvent(AccountDowngraded $event)
    {
        $event->account->deleteFinicityCustomer();
    }
}
