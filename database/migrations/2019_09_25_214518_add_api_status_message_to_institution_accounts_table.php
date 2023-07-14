<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApiStatusMessageToInstitutionAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->string('api_status_message')->nullable();
        });
    }

    public function down()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->dropColumn('api_status_message');
        });
    }
}
