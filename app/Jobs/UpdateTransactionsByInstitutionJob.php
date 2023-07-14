<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\Institution;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class UpdateTransactionsByInstitutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $institution;
    protected $startDate;
    protected $endDate;
    protected $offset;

    public function __construct(Institution $institution, $startDate = null, $endDate = null, $offset = 0)
    {
        $updateTransactionsJob = $this;
        $twoWeeksAgo = Carbon::now()->subWeeks(2)->startOfDay();
        $today = Carbon::now()->endOfDay();
        $updateTransactionsJob->institution = $institution;
        $updateTransactionsJob->startDate = $startDate ?? $twoWeeksAgo;
        $updateTransactionsJob->endDate = $endDate ?? $today;
        $updateTransactionsJob->offset = $offset;
    }
    public function handle()
    {
        $updateTransactionsJob = $this;
        $jobKey = 'update_transactions_by_institution_' . $updateTransactionsJob->institution->id . '_' . $updateTransactionsJob->offset;
        Redis::throttle($jobKey)->allow(3)->every(60)->then(function () use ($updateTransactionsJob) {
            $updateTransactionsJob->institution->updateTransactions($updateTransactionsJob->startDate, $updateTransactionsJob->endDate, $updateTransactionsJob->offset);
        }, function () {
            return $this->release(60);
        });
    }
}
