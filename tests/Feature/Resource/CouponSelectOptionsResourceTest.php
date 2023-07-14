<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\CouponSelectOptionsResource as CouponSelectOptionsResourceV1;

class CouponSelectOptionsResourceTest extends TestCase
{
    public function testCouponSelectOptionsResourceV1Types()
    {
        // convert to resource
        $testResource = new CouponSelectOptionsResourceV1(null);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertTrue(is_array($testResource['coupon_types']));
        $this->assertTrue(is_array($testResource['reward_types']));
        if (count($testResource['coupon_types'])) {
            $this->assertIsString($testResource['coupon_types'][0]);
        }
        if (count($testResource['reward_types'])) {
            $this->assertIsString($testResource['reward_types'][0]);
        }
    }
}
