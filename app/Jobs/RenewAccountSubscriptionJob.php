<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\Account;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class RenewAccountSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    protected $account;

    public function __construct(Account $account)
    {
        $renewSubscriptionJob = $this;
        $renewSubscriptionJob->account = $account;
    }
    public function handle()
    {
        $renewSubscriptionJob = $this;
        $jobKey = 'renew_subscription_for_account_' . $renewSubscriptionJob->account->id;
        Redis::funnel($jobKey)->limit(1)->then(function () use ($renewSubscriptionJob) {
            $renewSubscriptionJob->account->refresh();
            $renewableStatuses = [
                'active',
                'free_trial'
            ];
            $today = Carbon::today();
            $expirationDate = new Carbon($renewSubscriptionJob->account->expire_date);
            $accountStatus = $renewSubscriptionJob->account->getOriginal('status');
            $shouldProcessRenewal = $accountStatus === 'pending_renewal' || (in_array($accountStatus, $renewableStatuses) && $expirationDate->lessThanOrEqualTo($today));
            if ($shouldProcessRenewal) {
                $renewSubscriptionJob->account->renewSubscription();
            }
        }, function () {
            return $this->release(240);
        });
    }
    public function failed(\Exception $exception)
    {
        $renewSubscriptionJob = $this;
        $renewSubscriptionJob->account->status = 'expired';
        $renewSubscriptionJob->account->save();
        $renewSubscriptionJob->account->sendExpirationNotificationToAllUsers();
    }
}
