<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastVerifiedEmailToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->string('last_verified_email')->nullable();
        });
        $verifiedUsers = DB::table('users')->where('email_verified', true)->get();
        foreach ($verifiedUsers as $user) {
            DB::table('users')->where('id', '=', $user->id)->update(['last_verified_email' => $user->email]);
        }
    }

    public function down()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn('last_verified_email');
        });
    }
}
