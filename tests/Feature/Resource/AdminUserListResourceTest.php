<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V2\AdminUserListResource as AdminUserListResourceV2;
use App\Http\Resources\V2\AdminUserListAccountResource as AdminUserListAccountResourceV2;
use App\Models\User;

class AdminUserListResourceTest extends TestCase
{
    public function testAdminUserListResourceV2Types()
    {
        // define model properties
        $testUser = User::factory()->create([
            'email_verified' => true,
            'email_verification_token' => 'foobar',
            'verification_requested_at' => new \DateTime(),
        ]);
        $testUser->accounts()->create();
        $testUser->refresh();
        //convert to resource
        $testResource = new AdminUserListResourceV2($testUser);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['accounts'][0] instanceof AdminUserListAccountResourceV2);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['name']);
        $this->assertIsString($testResource['email']);
        $this->assertIsInt($testResource['email_verified']);
        $this->assertIsString($testResource['email_verification_token']);
        $this->assertIsString($testResource['verification_requested_at']);
    }
}
