<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AllowNullAccountUserOnSentEmails extends Migration
{
    public function up()
    {
        Schema::table('sent_emails', function(Blueprint $table) {
            $table->unsignedInteger('account_user_id')->nullable()->change();
        });
    }

    public function down()
    {
        // cannot revert nullable column
    }
}
