<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTransactionsAllowNullInRemote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('remote_transaction_id')->nullable()->change();
            $table->datetime('remote_transaction_date')->nullable()->change();
            $table->string('remote_account_id')->nullable()->change();
            $table->string('remote_category')->nullable()->change();
            $table->string('remote_category_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Allow null is a one way change
    }
}
