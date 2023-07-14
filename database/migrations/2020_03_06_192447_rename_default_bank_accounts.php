<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameDefaultBankAccounts extends Migration
{
    public function up()
    {
        DB::table('bank_accounts')->where('slug', '=', 'everyday_checking')->where('name', '=', 'Everyday Checking')->update([
            'name' => 'Spending Account'
        ]);
        DB::table('bank_accounts')->where('slug', '=', 'monthly_bills')->where('name', '=', 'Monthly Bills Checking')->update([
            'name' => 'Bills Account'
        ]);

        Schema::table('defenses', function(Blueprint $table) {
            $table->renameColumn('everyday_checking_starting_balance', 'spending_account_starting_balance');
        });
    }

    public function down()
    {
        DB::table('bank_accounts')->where('slug', '=', 'everyday_checking')->where('name', '=', 'Spending Account')->update([
            'name' => 'Everyday Checking'
        ]);
        DB::table('bank_accounts')->where('slug', '=', 'monthly_bills')->where('name', '=', 'Bills Account')->update([
            'name' => 'Monthly Bills Checking'
        ]);

        Schema::table('defenses', function(Blueprint $table) {
            $table->renameColumn('spending_account_starting_balance', 'everyday_checking_starting_balance');
        });
    }
}
