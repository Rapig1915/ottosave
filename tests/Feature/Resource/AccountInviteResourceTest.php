<?php

namespace Tests\Feature\Resource;

use App\Http\Resources\V1\AccountInviteResource;
use App\Http\Resources\V1\AccountResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountInviteResourceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAccountInviteResourceV1Types()
    {
        $accountInviteResourceTest = $this;

        // define model properties
        $testPrimaryUser = User::factory()->create();
        $testAccount = $testPrimaryUser->accounts()->create();

        $testSecondaryUserName = 'Test Invitee';
        $testSecondaryUserEmail = 'testuser@gmail.com';
        $tesUserInvite = $testAccount->accountInvites()->create([
            'name' => $testSecondaryUserName,
            'email' => $testSecondaryUserEmail,
            'invite_code' => Str::random(20)
        ]);

        $tesUserInvite->refresh();

        //convert to resource
        $testResource = new AccountInviteResource($tesUserInvite);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $accountInviteResourceTest->assertTrue($testResource['account'] instanceof AccountResource);
        // test resource property types
        $accountInviteResourceTest->assertIsString($testResource['status']);
        $accountInviteResourceTest->assertTrue($testResource['status'] === 'pending');
        
        $accountInviteResourceTest->assertIsString($testResource['invite_code']);
        $accountInviteResourceTest->assertTrue(strlen($testResource['invite_code']) === 20);
        
        $accountInviteResourceTest->assertIsString($testResource['email']);
        $accountInviteResourceTest->assertIsInt($testResource['account_id']);
        $accountInviteResourceTest->assertTrue(is_array($testResource['all_role_names']));
    }
}
