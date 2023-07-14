<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\NotificationViewResource as NotificationViewResourceV1;

class NotificationViewResourceTest extends TestCase
{
    public function testNotificationViewResourceV1Types()
    {
        $mailPath = app_path('Mail/Notifications');

        if (!is_dir($mailPath)) {
            throw new \Exception('Email folder not found.', 404);
        }

        $files = scandir($mailPath);
        // unset the current and previous directory links
        unset($files[0]);
        unset($files[1]);

        $testResource = NotificationViewResourceV1::collection(collect($files));
        $testResource = $testResource->toArray(null);

        $this->assertIsString($testResource[2]);
    }
}
