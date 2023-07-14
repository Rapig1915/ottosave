<?php

namespace App\Models\BankAccount;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\BankAccount\ScheduleItem;
use App\Traits\HasAssignments;

class AssignableBankAccount extends BankAccount
{
    use HasAssignments;

    protected $table = 'bank_accounts';
    protected $with = ['untransferredAssignments'];

    protected static function boot()
    {
        parent::boot();
        bcscale(config('app.bcscale'));

        static::addGlobalScope(function($query) {
            $query->whereNotIn('slug', ['cc_payoff', 'income_deposit', 'savings_credit_card', 'primary_checking', 'primary_savings', 'unassigned'])->orWhereNull('slug');
            $query->whereIn('type', ['checking','savings']);
        });

        static::deleting(function ($assignableAccount) {
            $assignableAccount->untransferredAssignments()->delete();
            $assignedTransactionIds = $assignableAccount->assignments()->select('transaction_id')->get()->pluck('transaction_id')->all();
            Transaction::whereIn('id', $assignedTransactionIds)->update(['is_assignable' => false]);
        });
    }

    public function getBalanceAvailableAttribute()
    {
        $assignableAccount = $this;

        $assignableAccount->loadMissing(
            'institutionAccount',
            'unclearedAllocations',
            'unclearedAllocationsOut',
            'untransferredAssignments',
            'untransferredAssignments.transaction',
            'untransferredAssignments.allocations'
        );
        $assignableAccountBalance = $assignableAccount->balance_current ?: 0;
        $assignableAccountBalance = bcadd(bcsub($assignableAccountBalance, $assignableAccount->assignment_balance_adjustment), $assignableAccount->allocation_balance_adjustment);

        return round($assignableAccountBalance, 2);
    }

    public function getAllocationBalanceAdjustmentAttribute()
    {
        $assignableAccount = $this;
        $balanceAdjustment = 0;
        $assignableAccount->loadMissing(
            'unclearedAllocationsOut',
            'unclearedAllocations'
        );
        $unclearedAllocations = $assignableAccount->unclearedAllocations;
        if (!empty($unclearedAllocations)) {
            foreach ($unclearedAllocations as $unclearedAllocation) {
                $balanceAdjustment = bcadd($balanceAdjustment, $unclearedAllocation->amount);
            }
        }

        $unclearedAllocationsOut = $assignableAccount->unclearedAllocationsOut;
        if (!empty($unclearedAllocationsOut)) {
            foreach ($unclearedAllocationsOut as $unclearedAllocation) {
                $balanceAdjustment = bcsub($balanceAdjustment, $unclearedAllocation->amount);
            }
        }

        return round($balanceAdjustment, 2);
    }

    public function getAssignmentBalanceAdjustmentAttribute()
    {
        $assignableAccount = $this;

        $assignableAccount->loadMissing(
            'untransferredAssignments',
            'untransferredAssignments.transaction',
            'untransferredAssignments.allocations'
        );
        $untransferredAssignments = $assignableAccount->untransferredAssignments;
        $totalOfAssignmentTransactions = 0;
        $totalOfAssignmentAllocations = 0;
        if (!empty($untransferredAssignments)) {
            foreach ($untransferredAssignments as $untransferredAssignment) {
                $totalOfAssignmentTransactions = bcadd($totalOfAssignmentTransactions, $untransferredAssignment->transaction->amount);
                $totalOfAssignmentAllocations = bcadd($totalOfAssignmentAllocations, $untransferredAssignment->allocated_amount);
            }
        }

        $assignmentTotal = bcsub($totalOfAssignmentTransactions, $totalOfAssignmentAllocations);
        return round($assignmentTotal, 2);
    }

    public function initializeForDemo()
    {
        $assignableAccount = $this;
        if ($assignableAccount->slug === 'monthly_bills') {
            $assignableAccount->sub_account_order = 0;
            $demoScheduleItemPayloads = [
                [
                    'bank_account_id' => $assignableAccount->id,
                    'description' => 'Rent',
                    'amount_total' => 1200.00,
                    'type' => 'monthly',
                    'approximate_due_date' => 1
                ],
                [
                    'bank_account_id' => $assignableAccount->id,
                    'description' => 'Utilities',
                    'amount_total' => 120.00,
                    'type' => 'monthly',
                    'approximate_due_date' => 1
                ],
                [
                    'bank_account_id' => $assignableAccount->id,
                    'description' => 'Phone bill',
                    'amount_total' => 80.00,
                    'type' => 'monthly',
                    'approximate_due_date' => 5
                ],
                [
                    'bank_account_id' => $assignableAccount->id,
                    'description' => 'Car insurance',
                    'amount_total' => 1200.25,
                    'type' => 'yearly',
                    'approximate_due_date' => 'March'
                ],
                [
                    'bank_account_id' => $assignableAccount->id,
                    'description' => 'Car payment',
                    'amount_total' => 400.25,
                    'type' => 'monthly',
                    'approximate_due_date' => 19
                ],
            ];
            foreach ($demoScheduleItemPayloads as $demoScheduleItemPayload) {
                $scheduleItem = ScheduleItem::mergeOrCreate($demoScheduleItemPayload);
                $scheduleItem->save();
            }
        } elseif ($assignableAccount->name === 'Entertainment Account') {
            $demoScheduleItemPayload = [
                'bank_account_id' => $assignableAccount->id,
                'description' => 'Monthly goal',
                'amount_total' => 200.00,
                'type' => 'monthly',
            ];
            $scheduleItem = ScheduleItem::mergeOrCreate($demoScheduleItemPayload);
            $scheduleItem->save();
        }

        if (
            $assignableAccount->slug === 'monthly_bills' ||
            $assignableAccount->name === 'Entertainment Account' ||
            $assignableAccount->name === 'Vacation Account' ||
            $assignableAccount->name === 'Misc Account'
        ) {
            $primaryCheckingAccount = $assignableAccount->account->bankAccounts()->where('slug', 'primary_checking')->first();
            $assignableAccount->parent_bank_account_id = $primaryCheckingAccount->id ?? null;
            $assignableAccount->appears_in_account_list = !$primaryCheckingAccount->id ?? true;
            $assignableAccount->save();
        }
    }
}
