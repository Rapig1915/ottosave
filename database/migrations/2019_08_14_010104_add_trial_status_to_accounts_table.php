<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTrialStatusToAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->boolean('is_trial_used')->default(false);
        });
        DB::table('accounts')->whereNotNull('braintree_customer_id')->update(['is_trial_used' => true]);
    }

    public function down()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->dropColumn('is_trial_used');
        });
    }
}
