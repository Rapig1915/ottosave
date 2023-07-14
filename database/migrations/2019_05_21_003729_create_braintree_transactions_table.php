<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBraintreeTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('braintree_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('remote_transaction_id');
            $table->dateTime('transaction_date');
            $table->string('status');
            $table->decimal('total_amount', 15, 2);
            $table->unsignedInteger('account_id');
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
        Schema::dropIfExists('braintree_transactions');
    }
}
