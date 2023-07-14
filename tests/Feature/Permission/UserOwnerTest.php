<?php

namespace Tests\Feature\Permission;

use App\Http\Resources\V1\AccountUserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

class UserOwnerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUserOwnerPermissionTest()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $userOwnerTest = $this;
        // define model properties
        $testPrimaryUser = User::factory()->create();
        $testPrimaryUser->accounts()->create();

        $testAccountUser = $testPrimaryUser->accountUsers()->first();
        $testAccountUser->assignRole('owner');
        $testAccountUser->refresh();

        $testResource = new AccountUserResource($testAccountUser);

        $allRoleNames = $testResource['all_role_names'];
        $userOwnerTest->assertTrue(is_array($allRoleNames));
        $userOwnerTest->assertTrue(count($allRoleNames) === 1);
        $userOwnerTest->assertTrue($allRoleNames[0] === 'owner');
        
        $allPermissionNames = $testResource['all_permission_names'];
        $userOwnerTest->assertTrue(is_array($allPermissionNames));

        $ownerPermissions = [
            'edit account-users',
            'invite account-users',
            'update account-settings',
            'manage finicity-accounts',
        ];

        foreach($ownerPermissions as $ownerPermission){
            $userOwnerTest->assertTrue(in_array($ownerPermission, $allPermissionNames));
        }
    }
}
