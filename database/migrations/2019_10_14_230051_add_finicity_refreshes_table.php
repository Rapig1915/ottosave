<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFinicityRefreshesTable extends Migration
{
    public function up()
    {
        Schema::create('finicity_refreshes', function(Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('finicity_refreshable_id');
            $table->string('finicity_refreshable_type');
            $table->string('status');
            $table->text('error');
        });
    }

    public function down()
    {
        Schema::dropIfExists('finicity_refreshes');
    }
}
