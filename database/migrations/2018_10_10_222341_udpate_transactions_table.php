<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UdpateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('remote_category_id')->change();
            $table->string('remote_account_id')->change();
            $table->string('remote_transaction_id')->change();
            $table->boolean('pending')->default(false);
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('pending');
            $table->unsignedInteger('remote_category_id')->change();
            $table->unsignedInteger('remote_account_id')->change();
            $table->unsignedInteger('remote_transaction_id')->change();
        });
    }
}
