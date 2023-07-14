<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionTypeResource extends JsonResource
{
    public $subscriptionType;
    public function __construct($subscriptionType)
    {
        $resource = $this;
        $resource->subscriptionType = $subscriptionType;
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
            'price' => (float)$resource->subscriptionType['price'],
            'slug' => $resource->subscriptionType['slug'],
            'name' => $resource->subscriptionType['name'],
            'cleared_for_sale' => $resource->subscriptionType['cleared_for_sale']
        ];
    }
}
