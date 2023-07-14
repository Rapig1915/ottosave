<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransferWarningModalDismissedToNotificationPreferences extends Migration
{
    public function up()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->boolean('transfer_warning_modal_dismissed')->default(false);
        });
    }

    public function down()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->dropColumn('transfer_warning_modal_dismissed');
        });
    }
}
