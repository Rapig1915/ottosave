<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\BraintreeRenewalComplete;
use App\Services\External\TapfiliateService;
use App\Contracts\TapfiliateConvertable;

class CreateTapfiliateConversion implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    /**
     * Track conversion event in Tapfiliate
     *
     * @param  TapfiliateConvertable  $event
     * @return void
     */
    public function handle(TapfiliateConvertable $event)
    {
        $tapfiliateCustomer = $event->getTapfiliateCustomer();
        if ($tapfiliateCustomer) {
            try {
                $tapfiliateService = new TapfiliateService();
                $tapfiliateService->createConversionEvent(
                    $tapfiliateCustomer->customer_id,
                    $event->getConversionAmount(),
                    $event->getConversionId(),
                    $event->getCommissionType()
                );
            } catch (\Exception $e) {
                report($e);
            }
        }
    }
}
