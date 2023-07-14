<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFinicityOauthInstitutionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finicity_oauth_institutions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('old_institution_id');
            $table->string('new_institution_id');
            $table->text('transition_message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finicity_oauth_institutions');
    }
}
