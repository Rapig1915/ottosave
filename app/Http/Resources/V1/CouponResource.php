<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
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
            'number_of_uses' => (integer)$resource->number_of_uses,
            'reward_duration_in_months' => (integer)$resource->reward_duration_in_months,
        ];
    }
}
