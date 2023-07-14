<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIncomeBalanceUpdatedToAllocations extends Migration
{
    public function up()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->boolean('income_balance_updated')->default(0);
        });
        Schema::table('defenses', function(Blueprint $table) {
            $table->decimal('amount_to_allocate')->nullable();
        });
        DB::table('allocations')->where('transferred', '=', 1)->update(['income_balance_updated' => 1]);
    }

    public function down()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->dropColumn('income_balance_updated');
        });
        Schema::table('defenses', function(Blueprint $table) {
            $table->dropColumn('amount_to_allocate');
        });
    }
}
