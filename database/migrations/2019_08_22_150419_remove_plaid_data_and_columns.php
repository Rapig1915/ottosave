<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePlaidDataAndColumns extends Migration
{
    public function up()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->dropColumn('plaid_error');
            $table->dropColumn('plaid_metadata');
            $table->dropColumn('plaid_error_progress');
            $table->dropColumn('plaid_status');
            $table->dropColumn('webhook_url');
        });

        DB::table('institutions')->where('type', '=', 'plaid')->delete();
    }

    public function down()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->text('plaid_error')->nullable();
            $table->text('plaid_metadata')->nullable();
            $table->string('plaid_error_progress')->nullable();
            $table->json('plaid_status')->nullable();
            $table->string('webhook_url')->after('name')->nullable();
        });
    }
}
