<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\Account;

class CleanupExpiredAccountItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;

    public function __construct(Account $account)
    {
        $cleanupSubscriptionItemsJob = $this;
        $cleanupSubscriptionItemsJob->account = $account;
    }
    public function handle()
    {
        $cleanupSubscriptionItemsJob = $this;
        $cleanupSubscriptionItemsJob->account->cleanupSubscriptionItems();
    }
}
