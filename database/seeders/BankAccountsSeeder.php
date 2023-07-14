<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BankAccountsSeeder extends Seeder
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
        BankAccount::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $user = User::where('email', "testApp@buink.biz")->get()->first();
        $account = $user->current_account;

        $seedBankAccounts = [
            ['name' => 'Vacation Savings'],
            ['name' => 'Clothing Savings'],
            ['name' => 'Holiday/Gift Savings'],
            ['name' => 'Yearly Bills Savings'],
        ];

        $bankAccountsToCreate = array_merge(BankAccount::$defaultBankAccounts, $seedBankAccounts);
        foreach ($bankAccountsToCreate as $bankAccountPayload) {
            $bankAccountPayload['account_id'] = $account->id;
            $bankAccountPayload['balance_current'] = rand(100, 10000) / 10;
            $bankAccount = new BankAccount($bankAccountPayload);
            $bankAccount->save();

            if (!$bankAccount->is_required) {
                /* Attribute is_required is not available until save() */
                $bankAccount->purpose = 'savings';
                $bankAccount->color = $bankAccount->getNextDefaultColor();
                $bankAccount->save();
            }
        }
    }
}
