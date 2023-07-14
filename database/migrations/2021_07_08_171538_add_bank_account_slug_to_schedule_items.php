<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankAccountSlugToScheduleItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE schedule_items CHANGE COLUMN `bank_account_id` `bank_account_id` INT(10) UNSIGNED DEFAULT NULL');
        Schema::table('schedule_items', function (Blueprint $table) {
            $table->string('bank_account_slug')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('schedule_items')->whereNull('bank_account_id')->delete();
        DB::statement('ALTER TABLE schedule_items CHANGE COLUMN `bank_account_id` `bank_account_id` INT(10) UNSIGNED NOT NULL');
        Schema::table('schedule_items', function (Blueprint $table) {
            $table->dropColumn('bank_account_slug');
        });
    }
}
