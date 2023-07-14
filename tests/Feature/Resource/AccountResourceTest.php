<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\AccountResource as AccountResourceV1;
use App\Http\Resources\V1\MostRecentDefenseResource as MostRecentDefenseResourceV1;
use App\Http\Resources\V1\ActiveDiscountCouponResource as ActiveDiscountCouponResourceV1;
use App\Models\Account;

class AccountResourceTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAccountResourceV1Types()
    {
        // define model properties
        $testAccount = Account::factory()->create(
            [
                'braintree_customer_id' => 'foobar',
                'subscription_type' => 'monthly',
                'status' => 'active',
                'subscription_plan' => 'plus',
                'expire_date' => '2019-06-13',
                'subscription_provider' => 'braintree',
            ]
        );
        
        // temporarily store in database and refresh
        $testAccount->save();
        $testAccount->refresh();
        //convert to resource
        $testResource = new AccountResourceV1($testAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['most_recent_defense'] instanceof MostRecentDefenseResourceV1);
        $this->assertTrue($testResource['active_discount_coupon'] instanceof ActiveDiscountCouponResourceV1);
        // test resource property types
        $this->assertIsString($testResource['braintree_customer_id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['expire_date']);
        $this->assertIsInt($testResource['id']);
        $this->assertIsInt($testResource['projected_defenses_per_month']);
        $this->assertIsString($testResource['status']);
        $this->assertIsString($testResource['subscription_plan']);
        $this->assertIsString($testResource['subscription_type']);
        $this->assertIsString($testResource['subscription_provider']);
        $this->assertIsBool($testResource['is_trial_used']);
    }
}
