<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateInstitutionsTable extends Migration
{
    public function up()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->string('plaid_error_progress')->nullable();
        });
    }

    public function down()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->dropColumn('plaid_error_progress');
        });
    }
}
