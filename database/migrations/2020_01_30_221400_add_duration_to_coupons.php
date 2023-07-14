<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDurationToCoupons extends Migration
{
    public function up()
    {
        Schema::table('coupons', function(Blueprint $table) {
            $table->integer('reward_duration_in_months')->nullable();
        });
        Schema::table('account_coupon', function(Blueprint $table) {
            $table->integer('remaining_months')->default(0);
        });
    }

    public function down()
    {
        Schema::table('coupons', function(Blueprint $table) {
            $table->dropColumn('reward_duration_in_months');
        });
        Schema::table('account_coupon', function(Blueprint $table) {
            $table->dropColumn('remaining_months');
        });
    }
}
