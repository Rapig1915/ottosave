<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\NotificationPreferencesResource as NotificationPreferencesResourceV1;
use App\Models\User;

class NotificationPreferencesResourceTest extends TestCase
{
    public function testNotificationPreferencesResourceV1Types()
    {
        $testUser = User::factory()->create();
        $testUser->accounts()->create();
        $testAccountUser = $testUser->accountUsers()->first();
        $testPreferences = $testAccountUser->notificationPreferences;
        $testPreferences->account_user_id = $testAccountUser->id;
        $testPreferences->save();
        // update test model and convert to resource
        $testResource = new NotificationPreferencesResourceV1($testPreferences);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsInt($testResource['account_user_id']);
        $this->assertIsString($testResource['assignment_reminder_frequency']);
        $this->assertIsString($testResource['defense_reminder_frequency']);
        $this->assertIsBool($testResource['transfer_warning_modal_dismissed']);
    }
}
