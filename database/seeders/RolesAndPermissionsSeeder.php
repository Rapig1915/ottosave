<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DELETE FROM permission_role where 1');
        DB::statement('DELETE FROM roles where 1');
        DB::statement('DELETE FROM permissions where 1');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $seedRolesAndPermissions = RolesAndPermissionsSeeder::getSeedRolesAndPermissions();
        $allPermissions = $seedRolesAndPermissions['all-permissions'];

        // create permissions
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign permissions
        foreach ($seedRolesAndPermissions['roles-permissions'] as $role => $permissions) {
            $role = Role::firstOrCreate(['name' => $role]);
            $role->givePermissionTo($permissions);
        }
    }

    public static function getSeedRolesAndPermissions()
    {
        $adminPermissions = [
            'access super-admin',
        ];
        $ownerPermissions = [
            'edit account-users',
            'invite account-users',
            'update account-settings',
            'manage finicity-accounts',
        ];
        $coachPermissions = [];

        $allPermissions = array_merge($adminPermissions, $ownerPermissions, $coachPermissions);

        $rolesAndPermissions = [
            'super-admin' => $allPermissions,
            'owner' => $ownerPermissions,
            'coach' => $coachPermissions
        ];

        return [
            'all-permissions' => $allPermissions,
            'roles-permissions' => $rolesAndPermissions,
        ];
    }
}
