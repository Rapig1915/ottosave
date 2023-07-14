<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use \Carbon\Carbon;

class AddDefenseIntervalToAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->string('defense_interval')->default('monthly');
        });
        Schema::table('defenses', function(Blueprint $table) {
            $table->timestamp('end_date')->nullable();
        });
        Schema::table('allocations', function(Blueprint $table) {
            $table->decimal('balance_at_defense')->nullable();
        });
        $defenses = DB::table('defenses')->get();
        foreach ($defenses as $defense) {
            $endDate = new Carbon($defense->created_at);
            $endDate->endOfMonth();
            $defense->end_date = $endDate;
            DB::table('defenses')->where('id', '=', $defense->id)->update(['end_date' => $endDate]);
        }

        $allocations = DB::table('allocations')->get();
        foreach ($allocations as $allocation) {
            $bankAccount = DB::table('bank_accounts')->find($allocation->bank_account_id);
            $isBankAccountLinked = $bankAccount->institution_account_id;
            if ($isBankAccountLinked) {
                $institutionAccount = DB::table('institution_accounts')->find($bankAccount->institution_account_id);
                $currentBalance = $institutionAccount->balance_current;
            } else {
                $currentBalance = $bankAccount->balance_current;
            }

            $transactionsSinceDefense = DB::table('transactions')->where('bank_account_id', '=', $bankAccount->id)->where('remote_transaction_date', '>', $allocation->created_at)->sum('amount');
            $balance_at_defense = $currentBalance + $transactionsSinceDefense;
            DB::table('allocations')->where('id', '=', $allocation->id)->update(['balance_at_defense' => $balance_at_defense]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->dropColumn('defense_interval');
        });
        Schema::table('defenses', function(Blueprint $table) {
            $table->dropColumn('end_date');
        });
        Schema::table('allocations', function(Blueprint $table) {
            $table->dropColumn('balance_at_defense');
        });
    }
}
