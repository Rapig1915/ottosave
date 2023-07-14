<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\ITunesReceipt;
use App\Contracts\TapfiliateConvertable;

class ITunesRenewalComplete implements TapfiliateConvertable
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $iTunesReceipt;

    public function __construct(ITunesReceipt $iTunesReceipt)
    {
        $this->iTunesReceipt = $iTunesReceipt;
    }

    public function getTapfiliateCustomer()
    {
        return $this->iTunesReceipt->account->tapfiliate_customer;
    }

    public function getConversionAmount()
    {
        // itunes receipts do not provide information about the purchase amount
        return 0;
    }

    public function getCommissionType()
    {
        $productId = $this->iTunesReceipt->product_id;
        $commissionType = preg_replace("/\./", "-", $productId);
        return $commissionType;
    }

    public function getConversionId()
    {
        return "ios-{$this->iTunesReceipt->id}";
    }
}
