<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotificationPreferenceForDefenseReminder extends Migration
{
    public function up()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->string('defense_reminder_frequency')->default('weekly');
        });
    }

    public function down()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->dropColumn('defense_reminder_frequency');
        });
    }
}
