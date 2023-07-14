<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteAllInstitutions extends Migration
{
    public function up()
    {
        // copy institution_account balances to bank_accounts
        $linkedBankAccounts = DB::table('bank_accounts')->whereNotNull('institution_account_id')
            ->leftJoin('institution_accounts', 'bank_accounts.institution_account_id', '=', 'institution_accounts.id')
            ->select('bank_accounts.*', 'institution_accounts.balance_current as institution_balance')
            ->get();
        foreach ($linkedBankAccounts as $bankAccount) {
            DB::table('bank_accounts')->where('id', $bankAccount->id)->update(['balance_current' => $bankAccount->institution_balance]);
        }

        // remove all institutions
        DB::table('institutions')->delete();

        // remove all unassigned remote transactions
        $assignments = DB::table('assignments')->get();
        $assignedTransactionIds = collect($assignments)->pluck('transaction_id')->all();
        DB::table('transactions')->whereNotIn('id', $assignedTransactionIds)->whereNotNull('remote_transaction_id')->where('remote_transaction_id', '!=', '')->delete();
    }

    public function down()
    {
        // cannot undelete records
    }
}
