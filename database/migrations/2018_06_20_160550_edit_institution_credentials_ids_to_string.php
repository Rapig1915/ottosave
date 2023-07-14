<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditInstitutionCredentialsIdsToString extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_credentials', function (Blueprint $table) {
            $table->string('institution_id')->change();
            $table->string('remote_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institution_credentials', function (Blueprint $table) {
            $table->unsignedInteger('institution_id')->change();
            $table->unsignedInteger('remote_id')->change();
        });
    }
}
