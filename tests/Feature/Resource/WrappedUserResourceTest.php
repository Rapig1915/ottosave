<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\WrappedUserResource as WrappedUserResourceV1;
use App\Http\Resources\V1\UserResource as UserResourceV1;
use App\Http\Resources\V1\UserDetailsResource as UserDetailsResourceV1;
use App\Models\User;

class WrappedUserResourceTest extends TestCase
{
    public function testWrappedUserResourceV1Types()
    {
        $testUser = User::factory()->create([
            'email_verification_token' => 'foobar',
            'email_verified' => false
        ]);
        // update test model and convert to resource
        $testUser->refresh();
        $testResource = new WrappedUserResourceV1($testUser);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['user'] instanceof UserDetailsResourceV1);
    }
}
