<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class IosProductResource extends JsonResource
{
    public $iosProduct;

    public function __construct($iosProduct)
    {
        $resource = $this;
        $resource->iosProduct = $iosProduct;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this;
        return [
            'product_id' => $resource->iosProduct['product_id'],
            'billing_interval' => $resource->iosProduct['billing_interval'],
            'free_trial_period' => $resource->iosProduct['free_trial_period'],
        ];
    }
}
