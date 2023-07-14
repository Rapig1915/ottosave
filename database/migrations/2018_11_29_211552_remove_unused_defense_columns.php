<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUnusedDefenseColumns extends Migration
{
    public function up()
    {
        Schema::table('defenses', function(Blueprint $table) {
            $table->dropColumn('amount_to_allocate');
        });
        Schema::table('accounts', function(Blueprint $table) {
            $table->dropColumn('defense_interval');
        });
    }

    public function down()
    {
        Schema::table('defenses', function(Blueprint $table) {
            $table->decimal('amount_to_allocate')->nullable();
        });
        Schema::table('accounts', function(Blueprint $table) {
            $table->string('defense_interval')->default('monthly');
        });
    }
}
