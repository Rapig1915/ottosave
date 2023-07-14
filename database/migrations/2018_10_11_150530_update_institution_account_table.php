<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateInstitutionAccountTable extends Migration
{
    public function up()
    {
        Schema::table('institution_accounts', function (Blueprint $table) {
            $table->renameColumn('account_id', 'remote_id');
        });
    }

    public function down()
    {
        Schema::table('institution_account', function (Blueprint $table) {
            $table->renameColumn('remote_id', 'account_id');
        });
    }
}
