<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterBankAccountsIconAndType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_accounts', function($table){
            $table->string('icon')->default('square')->change();
            $table->string('type')->default('savings')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_accounts', function($table){
            $table->string('icon')->default('credit-card')->change();
            $table->string('type')->default(false)->change();
        });
    }
}