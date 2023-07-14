<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyAllocationsTable extends Migration
{
    public function up()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->renameColumn('income_deposit_id', 'transferred_from_id');
            $table->renameColumn('income_balance_updated', 'cleared_out');
            $table->unsignedInteger('defense_id')->nullable()->change();
        });
        Schema::table('transactions', function(Blueprint $table) {
            $table->decimal('amount', 8, 2)->change();
            $table->unsignedInteger('allocation_id')->nullable();
            $table->foreign('allocation_id')->references('id')->on('allocations')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->renameColumn('transferred_from_id', 'income_deposit_id');
            $table->renameColumn('cleared_out', 'income_balance_updated');
        });
        Schema::table('transactions', function(Blueprint $table) {
            $table->float('amount', 8, 2)->change();
            $table->dropForeign('transactions_allocation_id_foreign');
            $table->dropColumn('allocation_id');
        });
    }
}
