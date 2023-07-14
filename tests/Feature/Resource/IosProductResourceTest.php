<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\IosProductResource as IosProductResourceV1;
use App\Models\Account;

class IosProductResourceTest extends TestCase
{
    public function testIosProductResourceV1Types()
    {
        $testAccount = Account::factory()->create();
        $iosProducts = $testAccount->getIosSubscriptionProducts();
        // update test model and convert to resource
        $testResource = new IosProductResourceV1($iosProducts[0]);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsString($testResource['product_id']);
        $this->assertIsString($testResource['billing_interval']);
        $this->assertIsString($testResource['free_trial_period']);
    }
}
