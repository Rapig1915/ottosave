<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItunesReceiptTable extends Migration
{
    public function up()
    {
        Schema::create('itunes_receipts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->unsignedInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->string('transaction_id');
            $table->string('original_transaction_id');
            $table->text('encoded_receipt');

            $table->string('product_id')->nullable();
            $table->string('expires_date')->nullable();
            $table->string('expiration_intent')->nullable();
            $table->string('is_in_billing_retry_period')->nullable();
            $table->boolean('is_trial_period')->nullable();
            $table->string('cancellation_date')->nullable();
            $table->string('auto_renew_status')->nullable();
            $table->string('auto_renew_product_id')->nullable();
            $table->string('price_consent_status')->nullable();
            $table->string('purchase_date')->nullable();
            $table->string('original_purchase_date')->nullable();
            $table->string('is_in_intro_offer_period')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('app_item_id')->nullable();
            $table->string('web_order_line_item_id')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('itunes_receipts');
    }
}
