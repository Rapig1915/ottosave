<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIncomeDepositIdToAllocations extends Migration
{
    public function up()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->unsignedInteger('income_deposit_id')->nullable();
            $table->foreign('income_deposit_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->decimal('amount', 8, 2)->change();
        });

        $allocations = DB::table('allocations')
            ->join('bank_accounts', 'allocations.bank_account_id', '=', 'bank_accounts.id')
            ->join('accounts', 'accounts.id', '=', 'bank_accounts.account_id')
            ->select('allocations.*', 'accounts.id as account_id')
            ->get();

        foreach ($allocations as $allocation) {
            $incomeAccount = DB::table('bank_accounts')->where('account_id', $allocation->account_id)->where('slug', '=', 'income_deposit')->first();
            if ($incomeAccount) {
                DB::table('allocations')->where('id', $allocation->id)->update(['income_deposit_id' => $incomeAccount->id]);
            }
        }
    }

    public function down()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->dropForeign('allocations_income_deposit_id_foreign');
            $table->dropColumn('income_deposit_id');
            $table->decimal('amount')->change();
        });
    }
}
