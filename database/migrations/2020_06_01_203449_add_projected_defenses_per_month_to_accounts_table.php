<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProjectedDefensesPerMonthToAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->integer('projected_defenses_per_month')->unsigned()->default(1);
        });
    }

    public function down()
    {
        Schema::table('accounts', function(Blueprint $table) {
            $table->dropColumn('projected_defenses_per_month');
        });
    }
}
