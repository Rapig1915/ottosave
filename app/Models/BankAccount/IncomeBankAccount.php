<?php

namespace App\Models\BankAccount;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\BankAccount;

class IncomeBankAccount extends BankAccount
{
    protected $table = 'bank_accounts';

    protected static function boot()
    {
        parent::boot();
        bcscale(config('app.bcscale'));

        static::addGlobalScope(function($query) {
            $query->where('type', '=', 'income');
        });
    }

    public function getBalanceAvailableAttribute()
    {
        $incomeAccount = $this;
        $incomeAccount->loadMissing(
            'unclearedAllocationsOut',
            'institutionAccount'
        );
        $incomeAccountBalance = $incomeAccount->balance_current ?: 0;
        $incomeAccountBalance = bcadd($incomeAccountBalance, $incomeAccount->allocation_balance_adjustment);
        return $incomeAccountBalance;
    }

    public function getAllocationBalanceAdjustmentAttribute()
    {
        $incomeAccount = $this;
        $balanceAdjustment = 0;
        $unclearedAllocations = $incomeAccount->unclearedAllocationsOut;
        if (!empty($unclearedAllocations)) {
            foreach ($unclearedAllocations as $unclearedAllocation) {
                $balanceAdjustment = bcsub($balanceAdjustment, $unclearedAllocation->amount);
            }
        }
        return $balanceAdjustment;
    }

    public function initializeForDemo()
    {
        $incomeAccount = $this;
        $primaryCheckingAccount = $incomeAccount->account->bankAccounts()->where('slug', 'primary_checking')->first();
        $incomeAccount->balance_current = 2000;
        $incomeAccount->parent_bank_account_id = $primaryCheckingAccount->id ?? null;
        $incomeAccount->appears_in_account_list = !$primaryCheckingAccount->id ?? true;
        $incomeAccount->sub_account_order = 2;
        $incomeAccount->save();
    }
}
