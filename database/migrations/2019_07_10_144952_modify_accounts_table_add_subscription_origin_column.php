<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyAccountsTableAddSubscriptionOriginColumn extends Migration
{
    public function up()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->string('subscription_origin')->default('web');
        });

        DB::table('accounts')->where('subscription_provider', 'itunes')->update(['subscription_origin' => 'ios']);
    }

    public function down()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->dropColumn('subscription_origin');
        });
    }
}
