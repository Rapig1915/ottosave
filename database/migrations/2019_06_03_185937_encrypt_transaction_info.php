<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EncryptTransactionInfo extends Migration
{
    public function up()
    {
        Schema::table('transactions', function(Blueprint $table) {
            $table->text('merchant')->nullable()->change();
            $table->text('remote_merchant')->nullable()->change();
        });
        $transactions = DB::table('transactions')->get();
        foreach ($transactions as $transaction) {
            $encryptedMerchant = encrypt($transaction->merchant);
            $encryptedRemoteMerchant = encrypt($transaction->remote_merchant);
            DB::table('transactions')->where('id', $transaction->id)->update(['merchant' => $encryptedMerchant, 'remote_merchant' => $encryptedRemoteMerchant]);
        }
    }

    public function down()
    {
        $transactions = DB::table('transactions')->get();
        foreach ($transactions as $transaction) {
            $decryptedMerchant = decrypt($transaction->merchant);
            $decryptedRemoteMerchant = decrypt($transaction->remote_merchant);
            DB::table('transactions')->where('id', $transaction->id)->update(['merchant' => $decryptedMerchant, 'remote_merchant' => $decryptedRemoteMerchant]);
        }
        Schema::table('transactions', function(Blueprint $table) {
            $table->string('merchant')->default('')->change();
            $table->string('remote_merchant')->default('')->change();
        });
    }
}
