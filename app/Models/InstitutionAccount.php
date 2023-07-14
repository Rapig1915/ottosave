<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use \App\Mail\Notifications\FinicityErrorReport;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Services\External\FinicityService;
use App\Jobs\FetchFinicityAccountTransactions;
use App\Jobs\ClearAllocationsJob;
use \Carbon\Carbon;
use \App\Models\SentEmail;
use \App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstitutionAccount extends Model
{
    use HasFactory;
    
    protected static function boot()
    {
        parent::boot();
        bcscale(config('app.bcscale'));

        static::deleting(function ($institutionAccount) {
            $revokedOAuthTokenStatusCodes = ['945', '946', '947'];
            $hasRemoteAccess = !in_array($institutionAccount->remote_status_code, $revokedOAuthTokenStatusCodes);
            if ($hasRemoteAccess) {
                $institutionAccount->removeRemoteAccount();
            }
            if ($institutionAccount->bankAccount) {
                $institutionAccount->bankAccount->delete();
            }
        });
    }

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount');
    }

    public function institution()
    {
        return $this->belongsTo('App\Models\Institution');
    }

    public function finicity_refreshes()
    {
        return $this->morphMany('App\Models\FinicityRefresh', 'finicity_refreshable');
    }

    public function initial_aggregation()
    {
        return $this->morphOne('App\Models\FinicityRefresh', 'finicity_refreshable')->where('update_type', 'initial_aggregation')->latest();
    }

    public function latest_finicity_refresh()
    {
        return $this->morphOne('App\Models\FinicityRefresh', 'finicity_refreshable')->where('update_type', '!=', 'initial_aggregation')->latest();
    }

    public function getBalanceAvailableAttribute()
    {
        $institutionAccount = $this;
        return round($institutionAccount->attributes['balance_available'], 2);
    }

    public function getBalanceCurrentAttribute()
    {
        $institutionAccount = $this;
        return round($institutionAccount->attributes['balance_current'], 2);
    }

    public function getBalanceLimitAttribute()
    {
        $institutionAccount = $this;
        return !is_null($institutionAccount->attributes['balance_limit']) ?
            round($institutionAccount->attributes['balance_limit'], 2) :
            null;
    }

    public function setApiStatusAttribute($status)
    {
        $institutionAccount = $this;
        $validValues = ['', 'good', 'recoverable', 'error'];
        $newValueIsValid = in_array($status, $validValues);
        if ($newValueIsValid) {
            $institutionAccount->attributes['api_status'] = $status;
        } else {
            throw new HttpException(422, "Attempting to set api_status to invalid value " . $status);
        }
    }

    public function setRemoteStatusCodeAttribute($statusCode)
    {
        $institutionAccount = $this;
        if (!$statusCode) {
            $statusCode = null;
        } else {
            $statusCode = (string)$statusCode;
        }
        $institutionAccount->attributes['remote_status_code'] = $statusCode;
        $institutionAccount->api_status_message = FinicityService::getErrorMessageFromAggregationStatusCode($institutionAccount->remote_status_code);
    }

    public function getApiStatusMessageAttribute()
    {
        $institutionAccount = $this;
        $apiStatusMessage = $institutionAccount->attributes['api_status_message'];
        $isAccountMigratedToOauth = $institutionAccount->remote_status_code === '948' && $institutionAccount->institution->credentials && $institutionAccount->institution->credentials->finicity_oauth_migration;
        if ($isAccountMigratedToOauth) {
            $oauthMigrationMessage = $institutionAccount->institution->credentials->finicity_oauth_migration->finicity_oauth_institution->transition_message;
            if ($oauthMigrationMessage) {
                $apiStatusMessage = $oauthMigrationMessage;
            }
        }
        return $apiStatusMessage;
    }

    public function getOrCreatePendingFinicityRefresh()
    {
        $institutionAccount = $this;
        $finicityRefresh = $institutionAccount->latest_finicity_refresh;
        if (!$finicityRefresh || $finicityRefresh->status !== 'pending') {
            $finicityRefresh = $institutionAccount->finicity_refreshes()->create([
                'status' => 'pending',
                'error' => ''
            ]);
        }
        return $finicityRefresh;
    }

    public function merge($payload)
    {
        $institutionAccount = $this;
        $institutionAccount->name = $payload['name'] ?? $institutionAccount->name ?? '';
    }

    public function updateBalancesFromFinicityAccount($finicityAccount)
    {
        $institutionAccount = $this;
        $institutionAccount->loadMissing(
            'bankAccount',
            'bankAccount.institutionAccount',
            'bankAccount.sub_accounts'
        );
        $institutionAccount->balance_limit = $finicityAccount->detail->creditMaxAmount ?? $institutionAccount->balance_limit ?? null;
        $institutionAccount->balance_current = $finicityAccount->balance;
        $institutionAccount->balance_available = $finicityAccount->detail->availableBalanceAmount ?? $finicityAccount->balance;
        $institutionAccount->remote_status_code = $finicityAccount->aggregationStatusCode ?? 0;
        $isFinicityAccountErrored = $institutionAccount->remote_status_code !== null && $institutionAccount->remote_status_code !== '0';
        if ($isFinicityAccountErrored) {
            $institutionAccount->handleFinicityAccountError($finicityAccount);
        } else {
            $institutionAccount->api_status = 'good';
        }
        return $institutionAccount;
    }

    public function removeRemoteAccount()
    {
        $institutionAccount = $this;
        $institutionAccount->loadMissing(
            'institution',
            'institution.account',
            'institution.account.finicity_customer'
        );
        if ($institutionAccount->institution->type === 'finicity') {
            $finicityService = new FinicityService();
            $customerId = $institutionAccount->institution->account->finicity_customer->customer_id;
            $finicityService->deleteAccount($customerId, $institutionAccount->remote_id);
        }
    }

    public function processTransctions($transactions, $allocationStartDate = null){
        $institutionAccount = $this;
        $bankAccount = $institutionAccount->bankAccount;
        $linkedAtDate = $institutionAccount->linked_at ? Carbon::parse($institutionAccount->linked_at) : Carbon::parse($institutionAccount->created_at);
        $newDepositTransactions = collect($transactions)->where('amount', '<', 0)->whereNull('id')->where('remote_transaction_date', '>=', $linkedAtDate);
        $newCCPaymentTransactions = collect($transactions)->where('amount', '>', 0)->whereNull('id')->where('remote_category', 'Credit Card Payment');
        $bankAccount->transactions()->saveMany($transactions);
        $bankAccount->transactions()->where('remote_transaction_id', '<>', '')->where('remote_transaction_date', '<', $linkedAtDate)->delete();

        $incomeSubAccount = $bankAccount->sub_accounts->where('slug', 'income_deposit')->first();
        if ($incomeSubAccount && $newDepositTransactions) {
            $totalDeposit = 0;
            foreach ($newDepositTransactions as $transaction) {
                $matchingAllocation = $bankAccount->allocations()
                    ->where('amount', '=', abs($transaction->amount))
                    ->doesntHave('transaction')
                    ->whereDate('created_at', '>=', $allocationStartDate ?? $linkedAtDate)
                    ->first();
                if ($matchingAllocation) {
                    $transaction->allocation_id = $matchingAllocation->id;
                    $transaction->save();
                } else {
                    $totalDeposit = bcadd($totalDeposit, abs($transaction->amount));
                }
            }
            if ($totalDeposit > 0) {
                $incomeSubAccount->balance_current = bcadd($incomeSubAccount->balance_current, $totalDeposit);
                $incomeSubAccount->save();
            }
        } elseif ($bankAccount->type === 'credit' && $newDepositTransactions) {
            $payoffAccount = $institutionAccount->institution->account->payoffAccount()->lockForUpdate()->first();
            $payoffAccount->refresh();
            $newAssignments = [];
            if ($payoffAccount) {
                $totalDeposit = 0;
                foreach ($newDepositTransactions as $depositTransaction) {
                    $assignment = Assignment::mergeOrCreate([
                        'transaction_id' => $depositTransaction->id,
                        'bank_account_id' => $payoffAccount->id
                    ]);
                    
                    $isCCPaymentTransaction = $depositTransaction->remote_category === 'Credit Card Payment';
                    if($isCCPaymentTransaction){
                        $newAssignments[] = $assignment;
                    }
                }
                if (count($newAssignments)) {
                    $payoffAccount->assignments()->saveMany($newAssignments);
                }
            }
        }

        if($bankAccount->type === 'checking' && $newCCPaymentTransactions){
            $payoffAccount = $institutionAccount->institution->account->payoffAccount()->lockForUpdate()->first();
            $payoffAccount->refresh();
            $newPlacements = [];

            if ($payoffAccount) {
                $totalCharge = 0;
                foreach ($newCCPaymentTransactions as $ccPaymentTransaction) {
                    $newTotalCharge = bcadd($totalCharge, abs($ccPaymentTransaction->amount));
                    $placement = new TransactionPlacement([
                        'transaction_id' => $ccPaymentTransaction->id,
                        'bank_account_id' => $payoffAccount->id
                    ]);
                    
                    $canChargeThisTransaction = bccomp($newTotalCharge, $payoffAccount->balance_current) <= 0;
                    if($canChargeThisTransaction){
                        $newPlacements[] = $placement;
                        $totalCharge = $newTotalCharge;
                    }
                }
                if (count($newPlacements)) {
                    $payoffAccount->transactionPlacements()->saveMany($newPlacements);
                    if (!$payoffAccount->institution_account_id) {
                        $payoffAccount->balance_current = bcsub($payoffAccount->balance_current, $totalCharge);
                        $payoffAccount->save();
                        if ($payoffAccount->parent_bank_account_id) {
                            $payoffAccount->parent_bank_account->refreshSubAccountBalances();
                        }
                    }
                }
            }
        }
    }

    public function storeFinicityTransactions(Carbon $startDate = null, Carbon $endDate = null, $offset = 1, $isSyncOperation = false, $ignoreDeposits = false)
    {
        $institutionAccount = $this;
        $institutionAccount->loadMissing(
            'bankAccount',
            'bankAccount.sub_accounts',
            'finicity_refreshes',
            'institution',
            'institution.credentials',
            'institution.account',
            'institution.account.finicity_customer',
            'institution.account.payoffAccount',
            'institution.account.payoffAccount.parent_bank_account'
        );
        $canStoreFinicityTransactions = $institutionAccount->bankAccount && $institutionAccount->institution->type === 'finicity';
        if ($canStoreFinicityTransactions) {
            $lastTransactionRefresh = $institutionAccount->finicity_refreshes->where('update_type', 'transactions')->sortByDesc('updated_at')->shift();
            $twoWeeksBeforeLastRefresh = $lastTransactionRefresh ? Carbon::parse($lastTransactionRefresh->updated_at)->subWeeks(2)->startOfDay() : Carbon::now()->subWeeks(2)->startOfDay();
            $linkedAtDate = $institutionAccount->linked_at ? Carbon::parse($institutionAccount->linked_at) : Carbon::parse($institutionAccount->created_at);
            $threeMonthsBeforeLinked = $linkedAtDate->copy()->subMonths(3)->startOfDay();
            $thisIsTheFirstRefresh = is_null($lastTransactionRefresh);

            /**
             * Re: #881 download transactions
             * Users want to download their transactions including the ones from 3 months before linked date.
             * But we don't want past transactions to affect current balance or appear as charges to be assigned.
             * So we soft delete the past ones right after we load them.
             */
            if($thisIsTheFirstRefresh){
                $defaultStartDate = $threeMonthsBeforeLinked;
            }else{
                $defaultStartDate = $twoWeeksBeforeLastRefresh;
            }

            $startDate = $startDate ?: $defaultStartDate;
            $endDate = $endDate ?: Carbon::now()->endOfDay();
            $finicityService = new FinicityService();
            $customerId = $institutionAccount->institution->account->finicity_customer->customer_id;
            $finicityResult = $finicityService->getAccountTransactions($customerId, $institutionAccount->remote_id, $startDate, $endDate, $offset);

            /**
             * Ignore request that do not return any transactions,
             * otherwise add unsaved transactions
             */
            if (sizeof($finicityResult['transactions']) > 0)
            {
                $transactions = collect($finicityResult['transactions'])->filter(function ($transaction) use ($ignoreDeposits) {
                    return !$ignoreDeposits || $transaction->amount < 0;
                });
                $unsavedTransactions = Transaction::bulkUpdateOrCreateFromRemoteTransactions($transactions, $institutionAccount->bankAccount, 'finicity');
                $institutionAccount->processTransctions($unsavedTransactions, $startDate);
            }

            if ($finicityResult['nextOffset']) {
                if ($isSyncOperation) {
                    FetchFinicityAccountTransactions::dispatchSync($institutionAccount, $startDate, $endDate, $finicityResult['nextOffset'], $isSyncOperation);
                } else {
                    FetchFinicityAccountTransactions::dispatch($institutionAccount, $startDate, $endDate, $finicityResult['nextOffset']);
                }
            } else {
                if ($isSyncOperation) {
                    ClearAllocationsJob::dispatchSync($institutionAccount->bankAccount);
                } else {
                    ClearAllocationsJob::dispatch($institutionAccount->bankAccount);
                }
                $finicityRefresh = $institutionAccount->getOrCreatePendingFinicityRefresh();
                $finicityRefresh->update([
                    'status' => 'complete',
                    'update_type' => 'transactions'
                ]);
                $institutionAccount->bankAccount->refreshSubAccountBalances();
            }
        }
    }

    public function handleFinicityAccountError($finicityAccount)
    {
        $institutionAccount = $this;
        $potentiallyRecoverableErrorCodes = [103, 185, 187, 931, 945, 946, 947, 948, 102, 320, 580, 900, 901, 903, 904, 905, 906, 907, 910, 915, 916];
        $shouldPromptForConnectFix = in_array($institutionAccount->remote_status_code, $potentiallyRecoverableErrorCodes) && !empty($finicityAccount->institutionLoginId);
        if ($shouldPromptForConnectFix) {
            $institutionAccount->api_status = 'recoverable';
            $institutionAccount->institution->credentials->remote_secret = $finicityAccount->institutionLoginId;
            $institutionAccount->institution->credentials->save();
        } elseif ($institutionAccount->api_status !== 'error') {
            $institutionAccount->api_status = 'error';
            $emailIdentifier = 'finicity_error_report_aid_' . $institutionAccount->institution->account->id . '_iid_' . $institutionAccount->institution->id;
            $oneDayAgo = Carbon::now()->subDays(1);
            $recentlySentEmails = SentEmail::where('email_identifier', $emailIdentifier)->whereDate('send_date', '>', $oneDayAgo)->get();
            if (count($recentlySentEmails) === 0) {
                $supportEmailAddress = config('mail.from.address');
                Mail::to($supportEmailAddress)->queue(new FinicityErrorReport($institutionAccount->institution, $finicityAccount));
                SentEmail::create([
                    'account_user_id' => null,
                    'email_identifier' => $emailIdentifier,
                    'send_date' => Carbon::now()
                ]);
            }
        }
    }
}
