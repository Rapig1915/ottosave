<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPendingColumnFromTransactions extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('pending');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('pending')->default(false);
        });
    }
}
