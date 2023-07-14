<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToInstitutions extends Migration
{
    public function up()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->json('plaid_status')->nullable();
        });
    }

    public function down()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->dropColumn('plaid_status');
        });
    }
}
