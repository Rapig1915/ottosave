<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRemoteStatusToInstitutionAccounts extends Migration
{
    public function up()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->string('remote_status_code')->nullable();
        });
    }

    public function down()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->dropColumn('remote_status_code');
        });
    }
}
