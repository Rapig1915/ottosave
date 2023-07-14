<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdateTypeToFinicityRefreshesTable extends Migration
{
    public function up()
    {
        Schema::table('finicity_refreshes', function(Blueprint $table) {
            $table->string('update_type')->nullable();
        });
    }

    public function down()
    {
        Schema::table('finicity_refreshes', function(Blueprint $table) {
            $table->dropColumn('update_type');
        });
    }
}
