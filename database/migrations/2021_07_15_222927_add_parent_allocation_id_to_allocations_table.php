<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentAllocationIdToAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->unsignedInteger('parent_allocation_id')->after('defense_id')->nullable();
            $table->foreign('parent_allocation_id')->references('id')->on('allocations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign('allocations_parent_allocation_id_foreign');
            $table->dropColumn('parent_allocation_id');
        });
    }
}
