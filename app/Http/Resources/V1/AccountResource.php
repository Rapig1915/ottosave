<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\MostRecentDefenseResource;
use App\Http\Resources\V1\ActiveDiscountCouponResource;

class AccountResource extends JsonResource
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
            'braintree_customer_id' => $resource->braintree_customer_id,
            'created_at' => $resource->created_at ? $resource->created_at->format('Y-m-d H:i:sP') : null,
            'updated_at' => $resource->updated_at ? $resource->updated_at->format('Y-m-d H:i:sP') : null,
            'expire_date' => $resource->expire_date,
            'id' => $resource->id,
            'most_recent_defense' => new MostRecentDefenseResource($resource->most_recent_defense),
            'status' => $resource->status,
            'subscription_plan' => $resource->subscription_plan,
            'subscription_type' => $resource->subscription_type,
            'subscription_provider' => $resource->subscription_provider,
            'is_trial_used' => $resource->is_trial_used,
            'projected_defenses_per_month' => $resource->projected_defenses_per_month,
            'active_discount_coupon' => new ActiveDiscountCouponResource($resource->getActiveDiscountCoupon())
        ];
    }
}
