<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('institution_id')->index()->nullable();
            $table->unsignedInteger('account_id')->index();
            $table->string('remote_name');
            $table->string('remote_official_name');
            $table->integer('mask');
            $table->string('type');
            $table->float('balance_available', 8, 2)->nullable();
            $table->string('remote_id');

            $table->timestamps();

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('set null');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_accounts');
    }
}
