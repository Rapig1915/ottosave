<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationPreferencesTable extends Migration
{
    public function up()
    {
        Schema::create('notification_preferences', function(Blueprint $table){
            $table->increments('id');
            $table->integer('account_user_id')->unsigned();
            $table->foreign('account_user_id')->references('id')->on('account_user')->onDelete('cascade');
            $table->string('assignment_reminder_frequency')->default('weekly');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_preferences');
    }
}
