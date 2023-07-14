<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterScheduleItemsTable extends Migration
{
    public function up()
    {
        Schema::table('schedule_items', function (Blueprint $table) {
            $table->date('date_end')->after('type')->nullable();

            $table->dropForeign(['schedule_id']);
            $table->dropColumn('schedule_id');

            $table->unsignedInteger('bank_account_id')->after('id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('schedule_items', function(Blueprint $table){
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn('bank_account_id');

            $table->unsignedInteger('schedule_id')->after('id');
            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('cascade');

            $table->dropColumn('date_end');
        });
    }
}
