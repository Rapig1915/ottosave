<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBraintreeTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('braintree_transactions', function(Blueprint $table) {
            $table->string('subscription_type')->nullable();
            $table->unsignedInteger('coupon_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('braintree_transactions', function(Blueprint $table) {
            $table->dropColumn('subscription_type');
            $table->dropColumn('coupon_id');
        });
    }
}
