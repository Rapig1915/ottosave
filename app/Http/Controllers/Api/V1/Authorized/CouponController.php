<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\Coupon;
use App\Http\Resources\V1\CouponResource;
use App\Http\Resources\V1\CouponSelectOptionsResource;

class CouponController extends Controller
{
    public function listCoupons()
    {
        $coupons = Coupon::all();
        return CouponResource::collection($coupons);
    }

    public function createCoupon(Request $request)
    {
        $coupon = Coupon::createFromPayload($request->input());
        $coupon->save();
        return new CouponResource($coupon);
    }

    public function getSelectOptions(Request $request)
    {
        return new CouponSelectOptionsResource(null);
    }

    public function redeemCoupon()
    {
        $coupon = Coupon::where('code', request()->input('code'))->first();
        if (!$coupon) {
            abort(400, 'Unable to redeem coupon with code: ' . request()->input('code'));
        }
        $coupon->attachToAccount(auth()->user()->current_account);
        $coupon->redeem(auth()->user()->current_account);
        return new CouponResource($coupon);
    }
}
