<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\External\FinicityService;
use App\Models\FinicityCustomer;
use App\Models\Institution;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class RefreshFinicityAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 240;
    protected $institutionId;
    protected $finicityCustomer;
    protected $startDate;

    public function __construct(FinicityCustomer $finicityCustomer, $institutionId = 'all', Carbon $startDate = null)
    {
        $refreshAccountsJob = $this;
        $refreshAccountsJob->institutionId = $institutionId;
        $refreshAccountsJob->finicityCustomer = $finicityCustomer;
        $refreshAccountsJob->startDate = $startDate;
    }

    public function handle()
    {
        $refreshAccountsJob = $this;
        $jobKey = 'refresh_finicity_accounts_' . $refreshAccountsJob->finicityCustomer->id . '_' . $refreshAccountsJob->institutionId;
        Redis::funnel($jobKey)->limit(1)->then(function () use ($refreshAccountsJob) {
            $finicityRefresh = $refreshAccountsJob->finicityCustomer->getOrCreatePendingFinicityRefresh();
            $finicityService = new FinicityService();
            $finicityService->refreshCustomerAccounts($refreshAccountsJob->finicityCustomer->customer_id);
            switch ($refreshAccountsJob->institutionId) {
                case 'all':
                    foreach ($refreshAccountsJob->finicityCustomer->account->institutions as $institution) {
                        $refreshAccountsJob->updateInstitutionAccounts($institution);
                    }
                    break;
                case 'none':
                    break;
                default:
                    $institution = $refreshAccountsJob->finicityCustomer->account->institutions()->findOrFail($refreshAccountsJob->institutionId);
                    $refreshAccountsJob->updateInstitutionAccounts($institution);
                    break;
            }
            $finicityRefresh->update([
                'status' => 'complete',
                'update_type' => 'customer_accounts'
            ]);
        }, function () use ($refreshAccountsJob) {
            return $this->release($refreshAccountsJob->timeout);
        });
    }

    private function updateInstitutionAccounts(Institution $institution)
    {
        $refreshAccountsJob = $this;
        $institution->getOrCreatePendingFinicityRefresh();
        InstitutionAccountBalanceJob::dispatch($institution);
        $linkedInstitutionAccounts = $institution->institutionAccount()->has('bankAccount')->get();
        foreach ($linkedInstitutionAccounts as $linkedInstitutionAccount) {
            $linkedInstitutionAccount->getOrCreatePendingFinicityRefresh();
            FetchFinicityAccountTransactions::dispatch($linkedInstitutionAccount, $refreshAccountsJob->startDate);
        }
    }

    public function failed(\Exception $exception)
    {
        $refreshAccountsJob = $this;
        $finicityRefresh = $refreshAccountsJob->finicityCustomer->getOrCreatePendingFinicityRefresh();
        $finicityRefresh->update(['status' => 'failed', 'error' => $exception->getMessage()]);
    }
}
