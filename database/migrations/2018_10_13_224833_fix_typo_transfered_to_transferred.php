<?php

use Illuminate\Database\Migrations\Migration;

class FixTypoTransferedToTransferred extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE allocations CHANGE transfered transferred tinyint(1) not null');
        DB::statement('ALTER TABLE assignments CHANGE transfered transferred tinyint(1) not null');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE allocations CHANGE transferred transfered tinyint(1) not null');
        DB::statement('ALTER TABLE assignments CHANGE transferred transfered tinyint(1) not null');
    }
}
