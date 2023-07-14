<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class BraintreeTokenResource extends JsonResource
{
    public $token;
    public $merchantId;

    public function __construct($token, $merchantId)
    {
        $resource = $this;
        $resource->token = $token;
        $resource->merchantId = $merchantId;
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
            'token' => $resource->token,
            'merchant_id' => $resource->merchantId
        ];
    }
}
