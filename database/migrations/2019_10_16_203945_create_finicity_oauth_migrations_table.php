<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFinicityOauthMigrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finicity_oauth_migrations', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('institution_credentials_id')->unsigned();
            $table->integer('finicity_oauth_institution_id')->unsigned();
            $table->string('status');
            $table->text('error');
            $table->foreign('institution_credentials_id')->references('id')->on('institution_credentials')->onDelete('cascade');
            $table->foreign('finicity_oauth_institution_id')->references('id')->on('finicity_oauth_institutions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finicity_oauth_migrations');
    }
}
