<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrimaryKeyToAllocationAssignmentTable extends Migration
{
    public function up()
    {
        Schema::table('allocation_assignment', function(Blueprint $table) {
            $table->increments('id')->first();
        });
    }

    public function down()
    {
        Schema::table('allocation_assignment', function(Blueprint $table) {
            $table->dropColumn('id');
        });
    }
}
