<?php

namespace App\Models\BankAccount;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;
use App\Models\Transaction;

class SavingsAccessCreditCard extends CreditCardAccount
{
    protected $table = 'bank_accounts';

    protected $with = ['unassignedTransactions'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(function($query) {
            $query->where('slug', '=', 'savings_credit_card');
        });
    }

    public function initializeForDemo()
    {
        $savingsAccessAccount = $this;
        $savingsAccessAccount->balance_current = 540;
        $savingsAccessAccount->appears_in_account_list = true;
        $savingsAccessAccount->save();
        $demoChargePayloads = [
            [
                'remote_transaction_date' => Carbon::now()->subDays(7),
                'amount' => 106,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Grocery store',
            ],
            [
                'remote_transaction_date' => Carbon::now()->subDays(6),
                'amount' => 32.00,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Restaurant',
            ],
            [
                'remote_transaction_date' => Carbon::now()->subDays(6),
                'amount' => 200.00,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Airline tickets',
            ],
            [
                'remote_transaction_date' => Carbon::now()->subDays(4),
                'amount' => 80.00,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Phone bill',
            ],
            [
                'remote_transaction_date' => Carbon::now()->subDays(4),
                'amount' => -100.00,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Credit card payment',
            ],
            [
                'remote_transaction_date' => Carbon::now()->subDays(3),
                'amount' => 67.15,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Online purchase',
            ],
            [
                'remote_transaction_date' => Carbon::now()->subDays(1),
                'amount' => 20,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Medical visit',
            ],
            [
                'remote_transaction_date' => Carbon::now()->subDays(1),
                'amount' => 35.15,
                'bank_account_id' => $savingsAccessAccount->id,
                'merchant' => 'Gas station',
            ],
        ];
        $demoCharges = [];
        foreach ($demoChargePayloads as $demoChargePayload) {
            $demoCharge = Transaction::mergeOrCreate($demoChargePayload);
            $demoCharges[] = $demoCharge;
        }
        $savingsAccessAccount->transactions()->saveMany($demoCharges);
    }
}
