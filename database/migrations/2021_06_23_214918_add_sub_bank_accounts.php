<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubBankAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_accounts', function(Blueprint $table) {
            $table->unsignedInteger('parent_bank_account_id')->after('account_id')->nullable();
            $table->foreign('parent_bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $table->unsignedInteger('sub_account_order')->after('parent_bank_account_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_accounts', function(Blueprint $table) {
            $table->dropForeign('bank_accounts_parent_bank_account_id_foreign');
            $table->dropColumn('parent_bank_account_id');
            $table->dropColumn('sub_account_order');
        });
    }
}
