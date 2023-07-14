<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BraintreeTransaction extends Model
{
    protected $table = 'braintree_transactions';

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public static function instantiateFromRemote($braintreeResponse)
    {
        $braintreeTransaction = new BraintreeTransaction();
        $braintreeTransaction->remote_transaction_id = $braintreeResponse->id;
        $braintreeTransaction->transaction_date = $braintreeResponse->createdAt;
        $braintreeTransaction->status = $braintreeResponse->status;
        $braintreeTransaction->total_amount = $braintreeResponse->amount;
        return $braintreeTransaction;
    }

}
