<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EncryptUserData extends Migration
{
    public function up()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->text('name')->change();
        });
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $encryptedName = encrypt($user->name);
            DB::table('users')->where('id', $user->id)->update(['name' => $encryptedName]);
        }
    }

    public function down()
    {
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $decryptedName = decrypt($user->name);
            DB::table('users')->where('id', $user->id)->update(['name' => $decryptedName]);
        }
        Schema::table('users', function(Blueprint $table) {
            $table->string('name')->change();
        });
    }
}
