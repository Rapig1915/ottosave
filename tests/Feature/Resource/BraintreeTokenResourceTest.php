<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\BraintreeTokenResource as BraintreeTokenResourceV1;

class BraintreeTokenResourceTest extends TestCase
{
    public function testBraintreeTokenResourceV1Types()
    {
        $testResource = new BraintreeTokenResourceV1('foo', 'bar');
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsString($testResource['token']);
        $this->assertIsString($testResource['merchant_id']);
    }
}
