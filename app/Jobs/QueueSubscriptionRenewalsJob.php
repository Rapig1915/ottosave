<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\Account;
use \Carbon\Carbon;

class QueueSubscriptionRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $renewableStatuses = [
            'active',
            'free_trial',
            'pending_renewal'
        ];
        $today = Carbon::today()->toDateString();
        Account::whereIn('status', $renewableStatuses)
        ->where('subscription_plan', '=', 'plus')
        ->whereDate('expire_date', '<=', $today)
        ->chunkById(100, function ($accounts) {
            foreach ($accounts as $account) {
                RenewAccountSubscriptionJob::dispatch($account);
            }
        });
    }
}
