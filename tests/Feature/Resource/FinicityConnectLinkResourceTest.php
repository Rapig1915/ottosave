<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\FinicityConnectLinkResource as FinicityConnectLinkResourceV1;
use App\Http\Resources\V2\FinicityConnectLinkResource as FinicityConnectLinkResourceV2;

class FinicityConnectLinkResourceTest extends TestCase
{
    public function testFinicityConnectLinkResourceV1Types()
    {
        $testResource = new FinicityConnectLinkResourceV1('foo');
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsString($testResource['connect_link']);
    }
    public function testFinicityConnectLinkResourceV2Types()
    {
        $testResource = new FinicityConnectLinkResourceV2('foo');
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsString($testResource['connect_link']);
    }
}
