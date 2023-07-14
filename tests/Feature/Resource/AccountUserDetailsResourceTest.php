<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\AccountUserDetailsResource as AccountUserDetailsResourceV1;
use App\Http\Resources\V1\NotificationPreferencesResource as NotificationPreferencesResourceV1;
use App\Models\User;

class AccountUserDetailsResourceTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAccountResourceV1Types()
    {
        // define model properties
        $testUser = User::factory()->create();
        $testUser->accounts()->create();
        $testAccountUser = $testUser->accountUsers()->first();
        // temporarily store in database
        $testAccountUser->refresh();
        //convert to resource
        $testResource = new AccountUserDetailsResourceV1($testAccountUser);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['notification_preferences'] instanceof NotificationPreferencesResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsInt($testResource['user_id']);
        $this->assertIsInt($testResource['account_id']);
        $this->assertTrue(is_array($testResource['all_permission_names']));
        $this->assertTrue(is_array($testResource['all_role_names']));
    }
}
