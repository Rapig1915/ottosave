<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\SubscriptionTypeResource as SubscriptionTypeResourceV1;
use App\Models\Account;

class SubscriptionTypeResourceTest extends TestCase
{
    public function testSubscriptionTypeResourceV1Types()
    {
        $testAccount = Account::factory()->create();
        $subscriptionTypes = $testAccount->getSubscriptionTypes();
        // update test model and convert to resource
        $testResource = new SubscriptionTypeResourceV1(collect($subscriptionTypes)->first());
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsFloat($testResource['price']);
        $this->assertIsString($testResource['slug']);
        $this->assertIsString($testResource['name']);
        $this->assertIsBool($testResource['cleared_for_sale']);
    }
}
