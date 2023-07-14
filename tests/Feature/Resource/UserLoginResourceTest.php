<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\UserLoginResource as UserLoginResourceV1;
use App\Http\Resources\V1\UserDetailsResource as UserDetailsResourceV1;
use App\Http\Resources\V1\RefreshTokenResource as RefreshTokenResourceV1;
use App\Models\User;

class UserLoginResourceTest extends TestCase
{
    public function testUserLoginResourceV1Types()
    {
        $testUser = User::factory()->create([
            'email_verification_token' => 'foobar',
            'email_verified' => false
        ]);
        // update test model and convert to resource
        $testUser->refresh();
        $testResource = new UserLoginResourceV1($testUser, []);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['user'] instanceof UserDetailsResourceV1);
        $this->assertTrue($testResource['token'] instanceof RefreshTokenResourceV1);
    }
}
