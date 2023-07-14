<?php

namespace App\Models\BankAccount;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\BankAccount;

class CreditCardAccount extends BankAccount
{
    protected $table = 'bank_accounts';

    protected $with = ['unassignedTransactions'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(function($query) {
            $query->where('type', '=', 'credit');
        });
    }

    public function unassignedTransactions()
    {
        $ccAccount = $this;
        return $ccAccount->hasMany('App\Models\Transaction','bank_account_id')
            ->whereDoesntHave('assignment')
            ->where('is_assignable', 1)
            ->orderBy('remote_transaction_date', 'desc');
    }

    public function getCountOfUnassignedTransactionsAttribute()
    {
        $ccAccount = $this;
        return count($ccAccount->unassignedTransactions);
    }

    public function getCountOfUntransferredAssignmentsAttribute()
    {
        $ccAccount = $this;
        return $ccAccount->transactions()->whereHas('assignment', function($query){
            $query->where('transferred', 0);
        })->count();
    }

    public function loadOverviewAttributes()
    {
        $ccAccount = $this;
        $ccAccount->setAppends([
            'is_required',
            'balance_available',
            'assignment_balance_adjustment',
            'allocation_balance_adjustment',
            'count_of_unassigned_transactions'
        ]);
    }

    public function getBalanceCurrentAttribute()
    {
        $ccAccount = $this;
        $ccAccountBalance = $ccAccount->attributes['balance_current'] ?? 0;
        if ($ccAccount->institutionAccount && !$ccAccount->is_balance_overridden) {
            $ccAccountBalance = $ccAccount->institutionAccount->balance_current;
        }
        $ccAccountBalance = abs($ccAccountBalance) * -1;
        return round($ccAccountBalance, 2);
    }
}
