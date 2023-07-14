<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAmountsToDecimalType extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE schedule_items CHANGE COLUMN `amount_total` `amount_total` DECIMAL(15, 2) NOT NULL');
        DB::statement('ALTER TABLE schedule_items CHANGE COLUMN `amount_monthly` `amount_monthly` DECIMAL(15, 2) NOT NULL');
        Schema::table('transactions', function(Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->decimal('balance_available', 15, 2)->nullable()->change();
            $table->decimal('balance_current', 15, 2)->nullable()->change();
            $table->decimal('balance_limit', 15, 2)->nullable()->change();
        });
        Schema::table('bank_accounts', function(Blueprint $table) {
            $table->decimal('balance_current', 15, 2)->default(0)->change();
        });
        Schema::table('assignments', function(Blueprint $table) {
            $table->decimal('allocated_amount', 15, 2)->default(0)->change();
        });
        Schema::table('allocations', function(Blueprint $table) {
            $table->decimal('amount', 15, 2)->default(0)->change();
            $table->decimal('balance_at_defense', 15, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->decimal('amount', 8, 2)->default(0)->change();
            $table->decimal('balance_at_defense', 8, 2)->nullable()->change();
        });
        Schema::table('assignments', function(Blueprint $table) {
            $table->decimal('allocated_amount', 8, 2)->default(0)->change();
        });
        Schema::table('bank_accounts', function(Blueprint $table) {
            $table->float('balance_current')->default(0)->change();
        });
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->float('balance_available')->nullable()->change();
            $table->float('balance_current')->nullable()->change();
            $table->float('balance_limit')->nullable()->change();
        });
        Schema::table('transactions', function(Blueprint $table) {
            $table->decimal('amount', 8, 2)->change();
        });
        DB::statement('ALTER TABLE schedule_items CHANGE COLUMN `amount_total` `amount_total` DOUBLE(8, 2) NOT NULL');
        DB::statement('ALTER TABLE schedule_items CHANGE COLUMN `amount_monthly` `amount_monthly` DOUBLE(8, 2) NOT NULL');
    }
}
