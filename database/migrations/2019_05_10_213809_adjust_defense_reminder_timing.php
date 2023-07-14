<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdjustDefenseReminderTiming extends Migration
{
    public function up()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->string('defense_reminder_frequency')->default('monthly')->change();
        });
        DB::table('notification_preferences')->where('defense_reminder_frequency', 'weekly')->update(['defense_reminder_frequency' => 'monthly']);
    }

    public function down()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->string('defense_reminder_frequency')->default('weekly')->change();
        });
        DB::table('notification_preferences')->where('defense_reminder_frequency', 'monthly')->update(['defense_reminder_frequency' => 'weekly']);
    }
}
