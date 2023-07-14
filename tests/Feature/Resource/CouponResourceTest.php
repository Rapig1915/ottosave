<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\CouponResource as CouponResourceV1;
use App\Models\Coupon;

class CouponResourceTest extends TestCase
{
    public function testCouponResourceV1Types()
    {
        $testCoupon = Coupon::factory()->create();
        // convert to resource
        $testResource = new CouponResourceV1($testCoupon);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsFloat($testResource['amount']);
        $this->assertIsString($testResource['type_slug']);
        $this->assertIsString($testResource['reward_type']);
        $this->assertIsString($testResource['code']);
        $this->assertIsString($testResource['expiration_date']);
        $this->assertIsInt($testResource['number_of_uses']);
        $this->assertIsInt($testResource['reward_duration_in_months']);
    }
}
