<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountInvitesTable extends Migration
{
    public function up()
    {
        Schema::create('account_invites', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('invite_code')->unique();
            $table->string('status')->default('pending');
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('account_invite_role', function(Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->unsignedInteger('account_invite_id');
            $table->foreign('account_invite_id')->references('id')->on('account_invites')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_invite_role');
        Schema::dropIfExists('account_invites');
    }
}
