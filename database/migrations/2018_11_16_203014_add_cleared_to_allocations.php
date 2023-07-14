<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClearedToAllocations extends Migration
{
    public function up()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->boolean('cleared')->after('transferred')->default(false);
        });
        DB::table('allocations')->where('transferred', '=', 1)->update(['cleared'=>1]);
    }

    public function down()
    {
        Schema::table('allocations', function(Blueprint $table) {
            $table->dropColumn('cleared');
        });
    }
}
