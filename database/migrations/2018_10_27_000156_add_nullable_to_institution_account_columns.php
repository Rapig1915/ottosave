<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullableToInstitutionAccountColumns extends Migration
{
    public function up()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->string('official_name')->nullable()->change();
            $table->string('iso_currency_code')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->string('official_name')->nullable(false)->change();
            $table->string('iso_currency_code')->nullable(false)->change();
        });
    }
}
