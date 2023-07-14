<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use \App\Models\Institution;
use \App\Models\BankAccount;
use \App\Services\External\FinicityService;
use \Carbon\Carbon;

class InstitutionAccountBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $institution;

    public $tries = 3;

    public function __construct(Institution $institution)
    {
        $initialAccountBalanceJob = $this;
        $initialAccountBalanceJob->institution = $institution;
    }
    public function handle()
    {
        $initialAccountBalanceJob = $this;
        $canConnectToFinicity = $initialAccountBalanceJob->institution->type === 'finicity' && $initialAccountBalanceJob->institution->credentials;
        if ($canConnectToFinicity) {
            $initialAccountBalanceJob->updateThroughFinicity();
        } else {
            $finicityRefresh = $initialAccountBalanceJob->institution->getOrCreatePendingFinicityRefresh();
            $finicityRefresh->update([
                'status' => 'complete',
                'update_type' => 'balance'
            ]);
        }
    }

    public function updateThroughFinicity()
    {
        $initialAccountBalanceJob = $this;
        $finicityRefresh = $initialAccountBalanceJob->institution->getOrCreatePendingFinicityRefresh();
        $customerId = $initialAccountBalanceJob->institution->account->finicity_customer->customer_id;
        $institutionId = $initialAccountBalanceJob->institution->credentials->remote_id;
        $finicityService = new FinicityService();
        $finicityAccounts = $finicityService->getCustomerAccountsByInstitution($customerId, $institutionId);
        foreach ($finicityAccounts as $finicityAccount) {
            $institutionAccount = $initialAccountBalanceJob->institution->institutionAccount()->with('bankAccount')->where('remote_id', $finicityAccount->id)->first();
            if ($institutionAccount) {
                $institutionAccount->updateBalancesFromFinicityAccount($finicityAccount);
                $institutionAccount->save();
                if ($institutionAccount->bankAccount) {
                    $institutionAccount->bankAccount->is_balance_overridden = false;
                    $institutionAccount->bankAccount->save();
                    $institutionAccount->bankAccount->refreshSubAccountBalances();
                }
            }

            $canUpdateLoginId = !empty($finicityAccount->institutionLoginId);
            $isLoginIdMissing = !$initialAccountBalanceJob->institution->credentials->remote_secret;
            $shouldUpdateCredentials = $canUpdateLoginId && ($isLoginIdMissing || $initialAccountBalanceJob->institution->credentials->remote_secret !== (string)$finicityAccount->institutionLoginId);
            if ($shouldUpdateCredentials) {
                $initialAccountBalanceJob->institution->credentials->remote_secret = $finicityAccount->institutionLoginId;
                $initialAccountBalanceJob->institution->credentials->save();
            }
        }
        $finicityRefresh->update([
            'status' => 'complete',
            'update_type' => 'balance'
        ]);
    }

    public function failed(\Exception $exception)
    {
        $initialAccountBalanceJob = $this;
        $finicityRefresh = $initialAccountBalanceJob->institution->getOrCreatePendingFinicityRefresh();
        $finicityRefresh->update(['status' => 'failed', 'error' => $exception->getMessage()]);
    }
}
