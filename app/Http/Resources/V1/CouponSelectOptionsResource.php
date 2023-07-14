<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Coupon;

class CouponSelectOptionsResource extends JsonResource
{
    public function toArray($request)
    {
        $resource = $this;
        return [
            'coupon_types' => Coupon::getCouponTypes(),
            'reward_types' => Coupon::getRewardTypes()
        ];
    }
}
