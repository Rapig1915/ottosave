<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\UserResource as UserResourceV1;
use App\Http\Resources\V1\AccountResource as AccountResourceV1;
use App\Http\Resources\V1\AccountUserResource as AccountUserResourceV1;
use App\Models\User;

class UserResourceTest extends TestCase
{
    public function testUserResourceV1Types()
    {
        $testUser = User::factory()->create([
            'email_verification_token' => 'foobar',
            'email_verified' => false
        ]);
        // update test model and convert to resource
        $testUser->refresh();
        $testResource = new UserResourceV1($testUser);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['current_account'] instanceof AccountResourceV1);
        $this->assertTrue($testResource['current_account_user'] instanceof AccountUserResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['email']);
        $this->assertIsString($testResource['name']);
        $this->assertIsString($testResource['email_verification_token']);
        $this->assertIsInt($testResource['email_verified']);
    }
}
