<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RelateAllocationsWithAssignments extends Migration
{
    public function up()
    {
        Schema::create('allocation_assignment', function( $table ){
            $table->integer('allocation_id')->unsigned();
            $table->foreign('allocation_id')->references('id')->on('allocations')->onDelete('cascade');
            $table->integer('assignment_id')->unsigned();
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
        });
        Schema::table('assignments', function(Blueprint $table) {
            $table->decimal('allocated_amount', 8, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('assignments', function(Blueprint $table) {
            $table->dropColumn('allocated_amount');
        });
        Schema::dropIfExists('allocation_assignment');
    }
}
