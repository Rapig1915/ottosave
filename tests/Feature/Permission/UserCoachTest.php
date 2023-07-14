<?php

namespace Tests\Feature\Permission;

use App\Http\Resources\V1\AccountUserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

class UserCoachTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUserCoachPermissionTest()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $userCoachTest = $this;
        // define model properties
        $testPrimaryUser = User::factory()->create();
        $testPrimaryUser->accounts()->create();

        $testAccountUser = $testPrimaryUser->accountUsers()->first();
        $testAccountUser->assignRole('coach');
        $testAccountUser->refresh();

        $testResource = new AccountUserResource($testAccountUser);

        $allRoleNames = $testResource['all_role_names'];
        $userCoachTest->assertTrue(is_array($allRoleNames));
        $userCoachTest->assertTrue(count($allRoleNames) === 1);
        $userCoachTest->assertTrue($allRoleNames[0] === 'coach');
        
        $allPermissionNames = $testResource['all_permission_names'];
        $userCoachTest->assertTrue(is_array($allPermissionNames));
        $userCoachTest->assertTrue(count($allPermissionNames) === 0);
    }
}
