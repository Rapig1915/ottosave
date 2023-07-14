<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccessTypeToAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->string('subscription_plan')->default('basic');
        });
        DB::table('accounts')->update(['subscription_plan' => 'plus']);
    }
    public function down()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->dropColumn('subscription_plan');
        });
    }
}
