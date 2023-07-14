<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimestampsToCoupons extends Migration
{
    public function up()
    {
        Schema::table('coupons', function(Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('coupons', function(Blueprint $table) {
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });
    }
}
