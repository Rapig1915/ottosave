<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionProviderToAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->string('subscription_provider')->default('braintree');
        });
    }

    public function down()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->dropColumn('subscription_provider');
        });
    }
}
