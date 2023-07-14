<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    public $timestamps = false;
    protected $fillable = [
        'name'
    ];

    public function accountUsers()
    {
        return $this->belongsToMany('App\Models\AccountUser');
    }

    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission');
    }

    public function givePermissionTo($permissionName)
    {
        $role = $this;
        if (is_array($permissionName)) {
            $permissions = Permission::whereIn('name', $permissionName)->select('id')->get();
            $permissionIds = $permissions->pluck('id');
            $role->permissions()->syncWithoutDetaching($permissionIds);
        } else {
            $permission = Permission::whereIn('name', $permissionName)->select('id')->get();
            $permissionId = $permission->pluck('id');
            $role->permissions()->syncWithoutDetaching($permissionId);
        }
    }

    public function hasPermissionTo($permissionName)
    {
        $role = $this;
        $hasPermission = $role->name === 'super-admin' || in_array($permissionName, $role->permissions->pluck('name')->all());
        return $hasPermission;
    }
}
