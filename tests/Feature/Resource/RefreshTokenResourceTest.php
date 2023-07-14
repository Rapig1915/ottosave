<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\RefreshTokenResource as RefreshTokenResourceV1;

class RefreshTokenResourceTest extends TestCase
{
    public function testRefreshTokenResourceV1Types()
    {
        $tokens = [
            'access_token' => 'foo',
            'expires_in' => 999,
            'refresh_expires_in' => 999,
            'refresh_token' =>' foo'
        ];
        $testResource = new RefreshTokenResourceV1($tokens);
        $testResource = $testResource->toArray(null);

        $this->assertIsString($testResource['access_token']);
        $this->assertIsInt($testResource['expires_in']);
        $this->assertIsInt($testResource['refresh_expires_in']);
        $this->assertIsString($testResource['refresh_token']);
    }
}
