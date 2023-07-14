<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBankAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function(Blueprint $table) {
            $table->dropForeign('bank_accounts_institution_id_foreign');
            $table->dropColumn('institution_id');

            $table->unsignedInteger('institution_account_id')->nullable()->change();
            $table->foreign('institution_account_id')->references('id')->on('institution_accounts')->onDelete('set null');
        });
    }
    public function down()
    {
        Schema::table('bank_accounts', function(Blueprint $table) {
            $table->dropForeign('bank_accounts_institution_accounts_id_foreign');
            $table->integer('institution_account_id')->nullable()->change();

            $table->unsignedInteger('institution_id')->index()->nullable();
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('set null');
        });
    }
}
