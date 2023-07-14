<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateDefaultCcName extends Migration
{
    public function up()
    {
        DB::table('bank_accounts')->where('slug', 'savings_credit_card')->update(['name' => 'Credit Card', 'color' => 'gold']);
    }

    public function down()
    {
        DB::table('bank_accounts')->where('slug', 'savings_credit_card')->update(['name' => 'DYM Credit Card', 'color' => 'gray-alt']);
    }
}
