<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTransactionsAddLocalDescription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('merchant', 'remote_merchant');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('merchant')->default('')->after('amount');
            $table->string('remote_merchant')->default('')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('remote_merchant')->change();
            $table->dropColumn('merchant');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('remote_merchant', 'merchant');
        });
    }
}
