<?php

namespace App\Models\BankAccount;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\BankAccount;
use \Carbon\Carbon;
use App\Models\BankAccount\ScheduleItem;

class SpendingAccount extends AssignableBankAccount
{
    protected $table = 'bank_accounts';

    protected static function boot()
    {
        parent::boot();
        bcscale(config('app.bcscale'));

        static::addGlobalScope(function($query) {
            $query->where('slug', '=', 'everyday_checking');
        });
    }
    public function getSpentAfterMostRecentDefenseAttribute()
    {
        $bankAccount = $this;
        $spent = 0;
        $mostRecentDefense = $bankAccount->account->most_recent_defense ?? null;
        if ($mostRecentDefense) {
            $allocationForDefense = $mostRecentDefense->allocation()
                ->where(function ($query) use ($bankAccount) {
                    $query->where('bank_account_id', '=', $bankAccount->id)
                        ->orWhere('transferred_from_id', '=', $bankAccount->id);
                })
                ->orderBy('created_at', 'asc')->first();
            if ($allocationForDefense) {
                $defenseAllocationAmount = $allocationForDefense->amount;
                if ($allocationForDefense->transferred_from_id === $bankAccount->id) {
                    $defenseAllocationAmount *= -1;
                }
                $defendedBalance = bcadd($mostRecentDefense->spending_account_starting_balance, $defenseAllocationAmount);
            } else {
                $defendedBalance = $mostRecentDefense->spending_account_starting_balance;
            }
            $spent = bcsub($defendedBalance, $bankAccount->balance_available);
        }
        return $spent;
    }
    public function loadOverviewAttributes()
    {
        $bankAccount = $this;
        $bankAccount->setAppends([
            'is_required',
            'balance_available',
            'assignment_balance_adjustment',
            'allocation_balance_adjustment',
            'spent_after_most_recent_defense'
        ]);
    }

    public function initializeForDemo()
    {
        $bankAccount = $this;
        $demoScheduleItemPayload = [
            'bank_account_id' => $bankAccount->id,
            'description' => 'Monthly spending',
            'amount_total' => 600.00,
            'type' => 'monthly',
        ];
        $scheduleItem = ScheduleItem::mergeOrCreate($demoScheduleItemPayload);
        $scheduleItem->save();
        $primaryCheckingAccount = $bankAccount->account->bankAccounts()->where('slug', 'primary_checking')->first();
        $bankAccount->parent_bank_account_id = $primaryCheckingAccount->id ?? null;
        $bankAccount->appears_in_account_list = !$primaryCheckingAccount->id ?? true;
        $bankAccount->sub_account_order = 1;
        $bankAccount->save();
    }
}
