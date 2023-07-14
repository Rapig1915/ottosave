<?php

namespace App\Models\BankAccount;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\BankAccount;
use App\Models\Assignment;
use App\Models\Transaction;
use App\Traits\HasAssignments;

class PayoffAccount extends BankAccount
{
    use HasAssignments;

    protected $table = 'bank_accounts';

    protected static function boot()
    {
        parent::boot();
        bcscale(config('app.bcscale'));

        static::addGlobalScope(function($query) {
            $query->where('slug', '=', 'cc_payoff');
        });

        static::deleting(function ($payoffAccount) {
            $assignedTransactionIds = $payoffAccount->assignments()->select('transaction_id')->get()->pluck('transaction_id')->all();
            Transaction::whereIn('id', $assignedTransactionIds)->update(['is_assignable' => false]);
        });
    }

    public function getAssignmentBalanceAdjustmentAttribute()
    {
        $payoffAccount = $this;
        $payoffAccount->loadMissing(
            'account',
            'account.assignableAccounts',
            'account.assignableAccounts.untransferredAssignments'
        );
        $totalOfAssignmentTransactions = 0;
        $totalOfAssignmentAllocations = 0;
        $untransferredAssignments = collect($payoffAccount->account->assignableAccounts)
            ->pluck('untransferredAssignments')
            ->flatten()
            ->where('bank_account_id', '!=', $payoffAccount->id)
            ->all();
        foreach ($untransferredAssignments as $untransferredAssignment) {
            $totalOfAssignmentTransactions = bcadd($totalOfAssignmentTransactions, $untransferredAssignment->transaction->amount);
            $totalOfAssignmentAllocations = bcadd($totalOfAssignmentAllocations, $untransferredAssignment->allocated_amount);
        }
        $assignmentTotal = bcsub($totalOfAssignmentTransactions, $totalOfAssignmentAllocations);
        return round($assignmentTotal, 2);
    }

    public function getAllocationBalanceAdjustmentAttribute()
    {
        $payoffAccount = $this;
        $payoffAccount->loadMissing('unclearedAllocations');
        $balanceAdjustment = 0;
        $unclearedAllocations = $payoffAccount->unclearedAllocations;
        if (!empty($unclearedAllocations)) {
            foreach ($unclearedAllocations as $unclearedAllocation) {
                $balanceAdjustment = bcadd($balanceAdjustment, $unclearedAllocation->amount);
            }
        }
        return $balanceAdjustment;
    }

    public function getBalanceAvailableAttribute()
    {
        $payoffAccount = $this;
        $payoffAccount->loadMissing(
            'institutionAccount',
            'unclearedAllocations',
            'account',
            'account.assignableAccounts',
            'account.assignableAccounts.assignments'
        );

        $balanceAdjustment = bcadd($payoffAccount->allocation_balance_adjustment, $payoffAccount->assignment_balance_adjustment);
        $balanceAvailable = bcadd($balanceAdjustment, $payoffAccount->balance_current);
        return $balanceAvailable;
    }

    public function initializeForDemo()
    {
        $payoffAccount = $this;
        $primaryCheckingAccount = $payoffAccount->account->bankAccounts()->where('slug', 'primary_checking')->first();
        $payoffAccount->parent_bank_account_id = $primaryCheckingAccount->id ?? null;
        $payoffAccount->appears_in_account_list = !$primaryCheckingAccount->id ?? true;
        $payoffAccount->sub_account_order = 3;
        $payoffAccount->balance_current = 100;
        $payoffAccount->save();
    }

    public function deleteAssignedPayments()
    {
        $payoffAccount = $this;
        $assignedTransactionIds = $payoffAccount->assignments()->select('transaction_id')->get()->pluck('transaction_id')->all();
        Transaction::whereIn('id', $assignedTransactionIds)->update(['is_assignable' => false]);
        $payoffAccount->assignments()->delete();
    }
}
