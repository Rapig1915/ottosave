<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlaidDataToInstitutions extends Migration
{
    public function up()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->text('plaid_error')->nullable();
            $table->text('plaid_metadata')->nullable();
        });
    }

    public function down()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->dropColumn('plaid_error');
            $table->dropColumn('plaid_metadata');
        });
    }
}
