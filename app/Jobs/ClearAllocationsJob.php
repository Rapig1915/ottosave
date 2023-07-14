<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\BankAccount;

class ClearAllocationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bankAccount;

    public function __construct(BankAccount $bankAccount)
    {
        $clearAllocationsJob = $this;
        $clearAllocationsJob->bankAccount = $bankAccount;
    }
    public function handle()
    {
        $clearAllocationsJob = $this;
        $clearAllocationsJob->bankAccount->loadMissing(
            'unclearedAllocations',
            'unclearedAllocationsOut',
            'transactions',
            'sub_accounts',
            'sub_accounts.unclearedAllocations',
            'sub_accounts.unclearedAllocationsOut'
        );
        $clearAllocationsJob->bankAccount->clearAllocations();
    }
}
