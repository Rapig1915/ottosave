<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddParentTransactionIdToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function(Blueprint $table) {
            $table->unsignedInteger('parent_transaction_id')->nullable();
            $table->foreign('parent_transaction_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('transactions', function(Blueprint $table) {
            $table->dropForeign('transactions_parent_transaction_id_foreign');
            $table->dropColumn('parent_transaction_id');
        });
    }
}
