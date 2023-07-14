<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterInstitutionCredentialsIndexAndFk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_credentials', function (Blueprint $table) {
            $table->unsignedInteger('institution_id')->index()->change();
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
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
            $table->dropForeign('institution_credentials_institution_id_foreign');
            $table->string('institution_id')->change();
        });
    }
}
