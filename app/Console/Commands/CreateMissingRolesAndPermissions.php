<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

class CreateMissingRolesAndPermissions extends Command
{
    protected $signature = 'buink:update-roles-and-permissions';
    protected $description = 'Adds missing roles and permssions from seeder';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        echo "adding missing roles and permssions from seeder...\n";
        $allRolesAndPermissions = RolesAndPermissionsSeeder::getSeedRolesAndPermissions();

        $neededPermissions = $allRolesAndPermissions['all-permissions'];
        $currentPermissions = Permission::whereIn('name', $neededPermissions)->get()->pluck('name')->all();
        $missingPermissions = collect($neededPermissions)->filter(function ($permission) use ($currentPermissions) {
            return !in_array($permission, $currentPermissions);
        });

        foreach ($missingPermissions as $permission) {
            // create missing permissions
            Permission::create(['name' => $permission]);
        }

        $neededRoles = collect($allRolesAndPermissions['roles-permissions'])->keys()->all();
        $currentRoles = Role::whereIn('name', $neededRoles)->get()->pluck('name')->all();
        $missingRoles = collect($neededRoles)->filter(function ($role) use ($currentRoles) {
            return !in_array($role, $currentRoles);
        });

        foreach ($missingRoles as $role) {
            // create missing roles
            $role = Role::create(['name' => $role, 'is_default' => true]);
        }

        $allRoles = Role::all();
        foreach ($allRoles as $role) {
            // add missing role-permissions
            $updatedPermissions = $allRolesAndPermissions['roles-permissions'][$role->name] ?? [];
            $role->givePermissionTo($updatedPermissions);
        }
    }
}
