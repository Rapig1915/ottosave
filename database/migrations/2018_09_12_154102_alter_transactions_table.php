<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Transaction;

class AlterTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Table maintenance
        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('remote_transaction_date')->after('remote_transaction_id');
            $table->renameColumn('description', 'merchant');
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('remote_transaction_date');
            $table->renameColumn('merchant', 'description');
        });
    }
}
