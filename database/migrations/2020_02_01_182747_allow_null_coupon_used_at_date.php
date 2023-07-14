<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AllowNullCouponUsedAtDate extends Migration
{
    public function up()
    {
        Schema::table('account_coupon', function(Blueprint $table) {
            $table->date('used_at')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        // cannot reverse nullable migration
    }
}
