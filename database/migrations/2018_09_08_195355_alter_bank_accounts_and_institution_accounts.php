<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterBankAccountsAndInstitutionAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_accounts', function($table){
            $table->string('mask')->change();
            $table->string('subtype')->after('mask');
        });
        Schema::table('bank_accounts', function($table){
            $table->dropColumn('mask')->change();
            $table->string('color')->after('balance_available');
            $table->string('icon')->after('color');
            $table->integer('institution_account_id')->nullable()->after('institution_id');
            $table->string('name')->after('account_id');
            $table->dropColumn('remote_name');
            $table->dropColumn('remote_official_name');
            $table->string('type')->nullable()->change();
            $table->integer('remote_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institution_accounts', function($table){
            $table->integer('mask')->change();
            $table->dropColumn('subtype');
        });
        Schema::table('bank_accounts', function($table){
            $table->integer('mask');
            $table->dropColumn('color');
            $table->dropColumn('icon');
            $table->dropColumn('institution_account_id');
            $table->dropColumn('name');
            $table->string('remote_name');
            $table->string('remote_official_name');
            $table->integer('type')->nullable(false)->change();
            $table->integer('remote_id')->nullable(false)->change();
        });
    }
}
