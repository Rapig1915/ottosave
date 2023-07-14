<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\BankAccount;
use App\Models\Institution;
use App\Models\AccountUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use \Carbon\Carbon;

class TransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Start fresh
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Transaction::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $bankAccounts = [];

        // Get relational info
        $testUser = User::where('email', "testApp@buink.biz")->get()->first();
        $bankAccounts[] = $testUser->current_account->savingsAccessCreditCard;

        $testDemoUser = User::where('email', "testDemo@buink.biz")->get()->first();
        $bankAccounts[] = $testDemoUser->current_account->bankAccounts()->where('slug', 'primary_checking')->first();

        // Transactions
        $seedTransactions = [
            [
                'amount' => 65.11,
                'remote_merchant' => 'OLD NAVY US 5335',
                'remote_transaction_id' => 1234,
                'remote_account_id' => 1234,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
            [
                'amount' => 50,
                'remote_merchant' => 'OLD NAVY US 5335',
                'remote_transaction_id' => 1235,
                'remote_account_id' => 1235,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
            [
                'amount' => 100,
                'remote_merchant' => 'APPLE STORE #R125',
                'remote_transaction_id' => 1236,
                'remote_account_id' => 1236,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
            [
                'amount' => 150,
                'remote_merchant' => 'THE HOME DEPOT',
                'remote_transaction_id' => 1237,
                'remote_account_id' => 1237,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
            [
                'amount' => 75.43,
                'remote_merchant' => 'CHILIS RSRNT 2345',
                'remote_transaction_id' => 1238,
                'remote_account_id' => 1238,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
            [
                'amount' => 88.88,
                'merchant' => 'Locally Created Transaction',
                'remote_transaction_id' => null,
                'remote_account_id' => null,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
            [
                'amount' => -189.00,
                'remote_merchant' => 'Deposit #Marquee',
                'remote_transaction_id' => 1239,
                'remote_account_id' => 1239,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
            [
                'amount' => -915.00,
                'remote_merchant' => 'Deposit #CitiCard',
                'remote_transaction_id' => 1240,
                'remote_account_id' => 1240,
                'remote_transaction_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ],
        ];

        foreach( $seedTransactions AS $thisTransactions ) {
            foreach($bankAccounts as $bankAccount){
                if(!$bankAccount){
                    continue;
                }
                
                $transaction = new Transaction( $thisTransactions );
                $transaction->bank_account_id = $bankAccount->id;
                $transaction->action_type = 'digital';     // digital, place, special or unresolved
                $transaction->remote_category = '';
                $transaction->remote_category_id = 0;
                $transaction->is_assignable = true;
                $transaction->save();
            }
        }
    }
}
