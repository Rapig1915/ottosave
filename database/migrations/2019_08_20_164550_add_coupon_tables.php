<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCouponTables extends Migration
{
    public function up()
    {
        Schema::create('coupons', function(Blueprint $table) {
            $table->increments('id');
            $table->double('amount', 10, 2);
            $table->string('type_slug');
            $table->string('reward_type');
            $table->string('code')->unique();
            $table->date('expiration_date')->nullable();
            $table->unsignedInteger('number_of_uses')->default(1);
        });

        Schema::create('account_coupon', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('coupon_id');

            $table->timestamp('used_at');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_coupon');
        Schema::dropIfExists('coupons');
    }
}
