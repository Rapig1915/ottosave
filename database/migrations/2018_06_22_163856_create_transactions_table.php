<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->float('amount', 8, 2);
            $table->string('description');
            $table->unsignedInteger('bank_account_id')->index();
            $table->string('action_type');
            $table->unsignedInteger('remote_transaction_id');
            $table->unsignedInteger('remote_account_id');
            $table->string('remote_category');
            $table->unsignedInteger('remote_category_id');
            $table->timestamps();

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
