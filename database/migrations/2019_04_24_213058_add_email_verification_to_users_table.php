<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmailVerificationToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->boolean('email_verified')->default(false);
            $table->string('email_verification_token')->nullable();
            $table->dateTime('verification_requested_at')->nullable();
        });
        DB::table('users')->update(['email_verified' => true]);
    }

    public function down()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn('email_verified');
            $table->dropColumn('email_verification_token');
            $table->dropColumn('verification_requested_at');
        });
    }
}
