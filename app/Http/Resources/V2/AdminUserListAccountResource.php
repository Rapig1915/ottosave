<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\MostRecentDefenseResource;
use App\Http\Resources\V2\InstitutionResource;

class AdminUserListAccountResource extends JsonResource
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
            'created_at' => $resource->created_at ? $resource->created_at->format('Y-m-d H:i:sP') : null,
            'updated_at' => $resource->updated_at ? $resource->updated_at->format('Y-m-d H:i:sP') : null,
            'expire_date' => $resource->expire_date,
            'status' => $resource->status,
            'subscription_plan' => $resource->subscription_plan,
            'subscription_provider' => $resource->subscription_provider,
            'subscription_origin' => $resource->subscription_origin,
            'subscription_type' => $resource->subscription_type,
            'braintree_customer_id' => $resource->braintree_customer_id,
            'institutions' => InstitutionResource::collection($resource->institutions),
            'finicity_customer' => $resource->finicity_customer,
        ];
    }
}
