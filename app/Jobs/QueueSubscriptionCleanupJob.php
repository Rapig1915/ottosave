<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\Account;
use \Carbon\Carbon;

class QueueSubscriptionCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {

    }
    public function handle()
    {
        Account::where(function ($query) {
            $graceStatuses = ['grace', 'trial_grace'];
            $today = Carbon::today()->toDateString();
            $query->whereIn('status', $graceStatuses)->whereDate('expire_date', '<=', $today);
        })->orWhere(function ($query) {
            $twoWeeksAgo = Carbon::today()->subWeeks(2)->toDateString();
            $query->where('status', 'expired')->whereDate('expire_date', '<=', $twoWeeksAgo);
        })->orWhere(function ($query) {
            $query->where('subscription_plan', 'basic')->has('finicity_customer');
        })->chunkById(100, function ($accounts) {
            foreach ($accounts as $account) {
                CleanupExpiredAccountItemsJob::dispatch($account);
            }
        });
    }
}
