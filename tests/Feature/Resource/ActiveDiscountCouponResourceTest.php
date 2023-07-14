<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
// use App\Http\Resources\V1\AccountResource as AccountResourceV1;
// use App\Http\Resources\V1\MostRecentDefenseResource as MostRecentDefenseResourceV1;
use App\Http\Resources\V1\ActiveDiscountCouponResource as ActiveDiscountCouponResourceV1;
use App\Models\Account;
use App\Models\Coupon;

class ActiveDiscountCouponResourceTest extends TestCase
{
    public function testActiveDiscountCouponResourceV1Types()
    {
        // define model properties
        $testAccount = Account::factory()->create();
        $testCoupon = Coupon::factory()->create([
            'reward_type' => 'discount_percentage'
        ]);
        // $testCoupon->reward_type = 'discount_percentage';
        $testCoupon->accounts()->attach($testAccount, ['used_at' => new \DateTime(), 'remaining_months' => 12]);
        // convert to resource
        $testResource = new ActiveDiscountCouponResourceV1($testAccount->getActiveDiscountCoupon());
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsFloat($testResource['amount']);
        $this->assertIsString($testResource['type_slug']);
        $this->assertIsString($testResource['reward_type']);
        $this->assertIsString($testResource['code']);
        $this->assertIsString($testResource['expiration_date']);
        $this->assertIsString($testResource['used_at']);
        $this->assertIsInt($testResource['remaining_months']);
    }
}
