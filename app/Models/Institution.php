<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Jobs\InstitutionAccountBalanceJob;
use App\Jobs\FetchFinicityAccountTransactions;
use App\Services\External\FinicityService;
use \Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Institution extends Model
{
    use HasFactory;
    
    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function credentials()
    {
        return $this->hasOne('App\Models\InstitutionCredentials');
    }

    public function institutionAccount()
    {
        return $this->hasMany('App\Models\InstitutionAccount');
    }

    public function finicity_refreshes()
    {
        return $this->morphMany('App\Models\FinicityRefresh', 'finicity_refreshable');
    }

    public function latest_finicity_refresh()
    {
        return $this->morphOne('App\Models\FinicityRefresh', 'finicity_refreshable')->latest();
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $institution = Institution::findOrFail($payload['id']);
        } else {
            $institution = new Institution;
        }

        $institution->type = $payload['type'] ?? '';
        $institution->name = $payload['name'] ?? '';

        return $institution;
    }

    public function updateTransactions($startDate = null, $endDate = null, $offset = 0, $isSyncOperation = false)
    {
        $institution = $this;
        $institution->loadMissing(
            'institutionAccount',
            'institutionAccount.bankAccount',
            'credentials'
        );
        if ($institution->type === 'finicity' && $institution->credentials) {
            $institution->updateTransactionsThroughFinicity($startDate, $endDate, $isSyncOperation);
        }
    }

    public function updateTransactionsThroughFinicity($startDate = null, $endDate = null, $isSyncOperation = false)
    {
        $institution = $this;
        foreach ($institution->institutionAccount as $institutionAccount) {
            $hasBankAccount = $institutionAccount->bankAccount;
            if ($isSyncOperation) {
                if ($hasBankAccount) {
                    FetchFinicityAccountTransactions::dispatchSync($institutionAccount, $startDate, $endDate, 1, $isSyncOperation);
                }
                InstitutionAccountBalanceJob::dispatchSync($institution);
            } else {
                if ($hasBankAccount) {
                    FetchFinicityAccountTransactions::dispatch($institutionAccount, $startDate, $endDate, 1);
                }
                InstitutionAccountBalanceJob::dispatch($institution);
            }
        }
    }

    public function updateDymBalances()
    {
        $institution = $this;
        $twoDaysAgo = Carbon::now()->subDays(2)->startOfDay();
        $today = Carbon::now()->endOfDay();
        if ($institution->type === 'finicity') {
            $institution->updateTransactions($twoDaysAgo, $today, 0, true);
        }
    }

    public function getOrCreatePendingFinicityRefresh()
    {
        $institution = $this;
        $finicityRefresh = $institution->finicity_refreshes()->where('status', '=', 'pending')->latest()->first();
        if (!$finicityRefresh) {
            $finicityRefresh = $institution->finicity_refreshes()->create([
                'status' => 'pending',
                'error' => ''
            ]);
        }
        return $finicityRefresh;
    }

    public function deleteRemoteInstitution()
    {
        $institution = $this;
        $institutionLoginId = $institution->credentials->remote_secret ?? null;
        $customerId = $institution->account->finicity_customer->customer_id ?? null;
        if ($institutionLoginId && $customerId) {
            $finicityService = new FinicityService();
            $finicityService->deleteInstitutionLogin($customerId, $institutionLoginId);
        }
    }
}
