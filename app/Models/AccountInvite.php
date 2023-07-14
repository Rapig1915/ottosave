<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountInvite extends Model
{
    protected $table = 'account_invites';
    protected $fillable = [
        'name',
        'email',
        'account_id',
        'invite_code',
        'status'
    ];

    protected $appends = [
        'all_role_names'
    ];

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'email', 'email');
    }

    public function getAllRoleNamesAttribute()
    {
        $accountInvite = $this;
        $assignedRoleNames = $accountInvite->roles->pluck('name')->all();
        return $assignedRoleNames;
    }

    public function markAccepted()
    {
        $accountInvite = $this;
        $accountInvite->status = 'accepted';
        $accountInvite->save();
    }

    public static function inviteUserToAccount($accountId, $name, $email, $roleIds = [])
    {
        $user = User::where('email', $email)->first();
        $accountUser = $user && $user->accountUsers()->where('account_id', $accountId)->first();
        if ($accountUser) {
            abort(400, 'This user already exists in your account.');
        }
        $previousInvite = AccountInvite::where('status', 'pending')->where('email', $email)->where('account_id', $accountId)->first();
        if ($previousInvite) {
            abort(400, 'This user has already been invited to your account.');
        }
        do {
            $inviteCode = bin2hex(random_bytes(20));
            $existingInviteWithCode = AccountInvite::where('status', 'pending')->where('invite_code', $inviteCode)->first();
        } while ($existingInviteWithCode);
        $accountInvite = AccountInvite::create([
            'name' => $name,
            'email' => $email,
            'account_id' => $accountId,
            'invite_code' => $inviteCode
        ]);
        $accountInvite->roles()->sync($roleIds);
        return $accountInvite;
    }

    public static function getByCodeAndEmail($inviteCode, $email)
    {
        $accountInvite = AccountInvite::with('account', 'roles')->where('invite_code', $inviteCode)->first();
        if (!$accountInvite) {
            abort(404, 'Invite code not found.');
        } elseif ($accountInvite->email !== $email) {
            abort(403, 'Invite code not valid for provided email.');
        }
        return $accountInvite;
    }

    /**
     * @return App/Models/User
     */
    public function getInviter()
    {
        $accountInvite = $this;
        $accountUserOwner = $accountInvite->account->accountUsers()->whereHas('roles', function($query){
            $query->whereIn('name', ['owner', 'super-admin']);
        })->first();
        return $accountUserOwner->user;
    }
}
