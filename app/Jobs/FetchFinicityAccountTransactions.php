<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\InstitutionAccount;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class FetchFinicityAccountTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $institutionAccount;
    protected $startDate;
    protected $endDate;
    protected $offset;
    protected $isSyncOperation;
    protected $ignoreDeposits;

    public function __construct(InstitutionAccount $institutionAccount, Carbon $startDate = null, Carbon $endDate = null, $offset = 1, $isSyncOperation = false, $ignoreDeposits = false)
    {
        $updateTransactionsJob = $this;
        $updateTransactionsJob->institutionAccount = $institutionAccount;
        $updateTransactionsJob->startDate = $startDate;
        $updateTransactionsJob->endDate = $endDate;
        $updateTransactionsJob->offset = $offset;
        $updateTransactionsJob->isSyncOperation = $isSyncOperation;
        $updateTransactionsJob->ignoreDeposits = $ignoreDeposits;
    }

    public function handle()
    {
        $updateTransactionsJob = $this;
        $jobKey = 'update_transactions_by_institution_account_' . $updateTransactionsJob->institutionAccount->id . '_' . $updateTransactionsJob->offset;
        Redis::throttle($jobKey)->allow(3)->every(60)->then(function () use ($updateTransactionsJob) {
            $finicityRefresh = $updateTransactionsJob->institutionAccount->getOrCreatePendingFinicityRefresh();
            $updateTransactionsJob->institutionAccount->storeFinicityTransactions(
                $updateTransactionsJob->startDate,
                $updateTransactionsJob->endDate,
                $updateTransactionsJob->offset,
                $updateTransactionsJob->isSyncOperation,
                $updateTransactionsJob->ignoreDeposits
            );
        }, function () {
            return $this->release(60);
        });
    }

    public function failed(\Exception $exception)
    {
        $updateTransactionsJob = $this;
        $finicityRefresh = $updateTransactionsJob->institutionAccount->getOrCreatePendingFinicityRefresh();
        $finicityRefresh->update(['status' => 'failed', 'error' => $exception->getMessage()]);
    }
}
