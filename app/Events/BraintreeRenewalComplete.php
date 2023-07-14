<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\BraintreeTransaction;
use App\Contracts\TapfiliateConvertable;

class BraintreeRenewalComplete implements TapfiliateConvertable
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $braintreeTransaction;

    public function __construct(BraintreeTransaction $braintreeTransaction)
    {
        $this->braintreeTransaction = $braintreeTransaction;
    }

    public function getTapfiliateCustomer()
    {
        return $this->braintreeTransaction->account->tapfiliate_customer;
    }

    public function getConversionAmount()
    {
        return $this->braintreeTransaction->total_amount;
    }

    public function getCommissionType()
    {
        return "default";
    }

    public function getConversionId()
    {
        return "braintree-{$this->braintreeTransaction->id}";
    }
}
