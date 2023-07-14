<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAssignableBooleanToTransactions extends Migration
{
    public function up()
    {
        Schema::table('transactions', function(Blueprint $table) {
            $table->boolean('is_assignable')->default(true);
        });
    }

    public function down()
    {
        Schema::table('transactions', function(Blueprint $table) {
            $table->dropColumn('is_assignable');
        });
    }
}
