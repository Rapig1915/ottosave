<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ActiveDiscountCouponResource extends JsonResource
{
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
            'id' => $resource->id,
            'amount' => (float)$resource->amount,
            'type_slug' => $resource->type_slug,
            'reward_type' => $resource->reward_type,
            'code' => $resource->code,
            'expiration_date' => $resource->expiration_date ? $resource->expiration_date->format('Y-m-d') : null,
            'used_at' => $resource->pivot->used_at,
            'remaining_months' => (integer)$resource->pivot->remaining_months
        ];
    }
}
