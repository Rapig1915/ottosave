<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Services\External\ITunesService;

class ITunesReceipt extends Model
{
    protected $table = 'itunes_receipts';

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public static function mergeOrCreateVerifiedReceipt($encodedReceipt, $receiptJson)
    {
        $iTunesReceipt = ITunesReceipt::where('transaction_id', $receiptJson->transaction_id)->first();
        $receiptPreviouslyStored = $iTunesReceipt instanceof ITunesReceipt;
        if (!$receiptPreviouslyStored) {
            $iTunesReceipt = new ITunesReceipt();
        }

        $iTunesReceipt->transaction_id = $receiptJson->transaction_id;
        $iTunesReceipt->original_transaction_id = $receiptJson->original_transaction_id ?? $receiptJson->transaction_id;
        $iTunesReceipt->encoded_receipt = $encodedReceipt;

        $iTunesReceipt->product_id = $receiptJson->product_id ?? null;
        $iTunesReceipt->expires_date = isset($receiptJson->expires_date) ? new \Carbon\Carbon($receiptJson->expires_date) : null;
        $iTunesReceipt->cancellation_date = isset($receiptJson->cancellation_date) ? new \Carbon\Carbon($receiptJson->cancellation_date) : null;
        $iTunesReceipt->original_purchase_date = isset($receiptJson->original_purchase_date) ? new \Carbon\Carbon($receiptJson->original_purchase_date) : null;
        $iTunesReceipt->purchase_date = isset($receiptJson->purchase_date) ? new \Carbon\Carbon($receiptJson->purchase_date) : null;
        $iTunesReceipt->expiration_intent = $receiptJson->expiration_intent ?? null;
        $iTunesReceipt->is_in_billing_retry_period = $receiptJson->is_in_billing_retry_period ?? null;
        $iTunesReceipt->is_trial_period = (isset($receiptJson->is_trial_period) && $receiptJson->is_trial_period === 'true') ? true : false;
        $iTunesReceipt->is_in_intro_offer_period = $receiptJson->is_in_intro_offer_period ?? null;
        $iTunesReceipt->auto_renew_status = $receiptJson->auto_renew_status ?? null;
        $iTunesReceipt->auto_renew_product_id = $receiptJson->auto_renew_product_id ?? null;
        $iTunesReceipt->price_consent_status = $receiptJson->price_consent_status ?? null;
        $iTunesReceipt->cancellation_reason = $receiptJson->cancellation_reason ?? null;
        $iTunesReceipt->app_item_id = $receiptJson->app_item_id ?? null;
        $iTunesReceipt->web_order_line_item_id = $receiptJson->web_order_line_item_id ?? null;

        return $iTunesReceipt;
    }

    public static function verifySubscriptionReceipt($encodedReceipt, $accountId)
    {
        $iTunesService = new ITunesService();
        $iTunesResponse = $iTunesService->verifyReceipt($encodedReceipt);
        $latestReceiptJson = null;
        foreach ($iTunesResponse->latest_receipt_info as $receiptJson) {
            if (!$latestReceiptJson) {
                $latestReceiptJson = $receiptJson;
            } else {
                $latestReceiptPurchaseDate = strtotime($latestReceiptJson->purchase_date);
                $receiptPurchaseDate = strtotime($receiptJson->purchase_date);
                if ($receiptPurchaseDate > $latestReceiptPurchaseDate) {
                    $latestReceiptJson = $receiptJson;
                }
            }
        }
        $iTunesReceipt = ITunesReceipt::mergeOrCreateVerifiedReceipt($iTunesResponse->latest_receipt, $latestReceiptJson);
        $iTunesReceipt->account_id = $accountId;
        $iTunesReceipt->save();
        return $iTunesReceipt;
    }
}
