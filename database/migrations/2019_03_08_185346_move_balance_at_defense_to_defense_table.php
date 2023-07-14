<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveBalanceAtDefenseToDefenseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('defenses', function(Blueprint $table) {
            $table->decimal('everyday_checking_starting_balance', 15, 2)->nullable();
        });
        $accounts = DB::table('accounts')->get();

        foreach ($accounts as $account) {
            // move balance_at_defense to defense table or create if doesn't exist
            $mostRecentDefense = DB::table('defenses')->where('account_id', $account->id)->latest()->first();
            if ($mostRecentDefense) {
                $everydayCheckingAccount = DB::table('bank_accounts')->where('account_id', $account->id)->where('slug', '=', 'everyday_checking')->first();
                if ($everydayCheckingAccount) {
                    $allocationIntoChecking = DB::table('allocations')->where('defense_id', $mostRecentDefense->id)->where('bank_account_id', '=', $everydayCheckingAccount->id)->oldest()->first();
                    if ($allocationIntoChecking) {
                        $mostRecentDefense->everyday_checking_starting_balance = $allocationIntoChecking->balance_at_defense;
                    } else {
                        // no balance_at_defense saved, set value to current balance
                        if ($everydayCheckingAccount->institution_account_id) {
                            $institutionAccount = DB::table('institution_accounts')->find($everydayCheckingAccount->institution_account_id);
                        }
                        $mostRecentDefense->everyday_checking_starting_balance = !empty($institutionAccount) ? $institutionAccount->balance_current : $everydayCheckingAccount->balance_current;
                        $institutionAccount = null;
                    }
                    DB::table('defenses')->where('id', $mostRecentDefense->id)->update(['everyday_checking_starting_balance' => $mostRecentDefense->everyday_checking_starting_balance]);
                }
            }
        }

        Schema::table('allocations', function(Blueprint $table) {
            $table->dropColumn('balance_at_defense');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('allocations', function(Blueprint $table) {
            $table->decimal('balance_at_defense', 15, 2)->nullable();
        });
        $accounts = DB::table('accounts')->get();
        foreach ($accounts as $account) {
            // move balance_at_defense to allocations table
            $mostRecentDefense = DB::table('defenses')->where('account_id', $account->id)->latest()->first();
            if ($mostRecentDefense) {
                $everydayCheckingAccount = DB::table('bank_accounts')->where('account_id', $account->id)->where('slug', '=', 'everyday_checking')->first();
                if ($everydayCheckingAccount) {
                    $allocationIntoChecking = DB::table('allocations')->where('defense_id', $mostRecentDefense->id)->where('bank_account_id', '=', $everydayCheckingAccount->id)->oldest()->first();
                    $allocationFromChecking = DB::table('allocations')->where('defense_id', $mostRecentDefense->id)->where('transferred_from_id', '=', $everydayCheckingAccount->id)->oldest()->first();
                    if ($allocationIntoChecking) {
                        DB::table('allocations')->where('id', $allocationIntoChecking->id)->update(['balance_at_defense' => $mostRecentDefense->everyday_checking_starting_balance]);
                    }
                    if ($allocationFromChecking) {
                        DB::table('allocations')->where('id', $allocationFromChecking->id)->update(['balance_at_defense' => $mostRecentDefense->everyday_checking_starting_balance]);
                    }
                }
            }
        }

        Schema::table('defenses', function(Blueprint $table) {
            $table->dropColumn('everyday_checking_starting_balance');
        });
    }
}
