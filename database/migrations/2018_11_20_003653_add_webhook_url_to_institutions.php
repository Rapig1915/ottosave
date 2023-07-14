<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWebhookUrlToInstitutions extends Migration
{
    public function up()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->string('webhook_url')->after('name')->nullable();
        });
    }

    public function down()
    {
        Schema::table('institutions', function(Blueprint $table) {
            $table->dropColumn('webhook_url');
        });
    }
}
