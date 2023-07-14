<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notifications\EmailVerification;
use App\Mail\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $guarded = [
        'email',
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'current_account',
        'current_account_user'
    ];

    protected $currentAccount;
    protected $currentAccountUser;

    public function accounts(){
        return $this->belongsToMany(Account::class);
    }

    public function accountUsers(){
        return $this->hasMany('App\Models\AccountUser');
    }

    public function getCurrentAccountAttribute(){
        $user = $this;
        if (empty($user->currentAccount)) {
            $request = request() ?? null;
            $accountId = (int)$request->header('current-account-id') ?? null;

            $user->setCurrentAccount($accountId);
        }

        return $user->currentAccount;
    }

    public function getCurrentAccountUserAttribute()
    {
        $user = $this;
        if (empty($user->currentAccountUser)) {
            $request = request() ?? null;
            $accountId = (int)$request->header('current-account-id') ?? null;

            $user->setCurrentAccount($accountId);
        }
        return $user->currentAccountUser;
    }

    public function setPasswordAttribute($password)
    {
        $user = $this;
        $user->attributes['password'] = bcrypt($password);
    }

    public function setNameAttribute($name)
    {
        $user = $this;
        $user->attributes['name'] = encrypt($name);
    }

    public function getNameAttribute()
    {
        $user = $this;
        return decrypt($user->attributes['name']);
    }

    public function getFirstNameAttribute()
    {
        $user = $this;
        $nameParts = explode(' ', $user->name);
        $firstName = reset($nameParts);
        return $firstName;
    }

    public function getLastNameAttribute()
    {
        $user = $this;
        $nameParts = explode(' ', $user->name);
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
        return $lastName;
    }

    public static function mergeOrCreate($payload)
    {

        if (isset($payload['id'])) {
            $user = User::findOrFail($payload['id']);
        } else {
            $user = new User;
        }

        $user->name = $payload['name'];

        return $user;
    }

    public function setCurrentAccount($accountId)
    {
        $user = $this;
        $userAccountHasChanged = empty($user->currentAccount) ||
            empty($user->currentAccountUser) ||
            $user->current_account->id !== $accountId ||
            $user->current_account_user->account_id !== $accountId;

        if ($userAccountHasChanged) {
            $user->currentAccount = $user->accounts()->find($accountId);
            if($user->currentAccount){
                $user->currentAccountUser = $user->accountUsers()->where('account_id', $accountId)->firstOrFail();
            }else{
                $accountUserOwner = $user->accountUsers()->whereHas('roles', function($query){
                    $query->where('name', 'owner');
                })->first();
                $user->currentAccount = $accountUserOwner ? $accountUserOwner->account : $user->accounts()->first();
                $user->currentAccountUser = $user->currentAccount ? $user->accountUsers()->where('account_id', $user->currentAccount->id)->firstOrFail() : $user->accountUsers()->first();
            }
        }

        return $user;
    }

    public function changeEmail($payload)
    {
        $user = $this;

        if ($user->isCurrentPassword($payload['current_password'])) {
            $user->email = $payload['email'];
            $user->email_verified = false;
            $user->verification_requested_at = null;
            $user->email_verification_token = null;
            $user->sendVerificationEmail();
        } else {
            throw new HttpException(403, "Permission denied.");
        }
    }

    public function changePassword($payload)
    {
        $user = $this;

        if ($user->isCurrentPassword($payload['current_password'])) {
            $user->password = $payload['password'];
        } else {
            throw new HttpException(403, "Permission denied.");
        }
    }

    public function isCurrentPassword($password)
    {
        $user = $this;

        return password_verify($password, $user->password);
    }

    public function sendVerificationEmail()
    {
        $user = $this;
        $lastEmailSentAt = $user->verification_requested_at ? new Carbon($user->verification_requested_at) : null;
        $twoHoursAgo = Carbon::now()->subHours(2);
        $rateLimitExceeded = $lastEmailSentAt && $twoHoursAgo->lessThan($lastEmailSentAt);
        if (!$rateLimitExceeded) {
            if (!$user->email_verification_token) {
                $user->email_verification_token = bin2hex(random_bytes(16));
            }
            Mail::to($user)->queue(new EmailVerification($user));
            $user->verification_requested_at = Carbon::now()->toDateTimeString();
            $user->save();
        }
    }

    public function sendPasswordResetNotification($token)
    {
        $user = $this;
        Mail::to($user)->queue(new ResetPasswordNotification($token,$user));
    }

    public function hasVerifiedEmail(){
        $user = $this;
        return $user->email_verified;
    }
    
    public function getAccessibleAccounts()
    {
        $user = $this;
        $accountUsers = $user->accountUsers;

        $accessibleAccounts = [];
        foreach($accountUsers as $accountUser){
            $account = [
                'account_id' => $accountUser->account_id,
                'roles' => $accountUser->all_role_names
            ];

            $hasNoRole = count($account['roles']) === 0 || (!in_array('owner', $account['roles']) && !in_array('coach', $account['roles']));
            if($hasNoRole){
                continue;
            }

            $isOwnerOfThisAccount = in_array('owner', $account['roles']);
            if($isOwnerOfThisAccount){
                $ownerUserOfThisAccount = $user;
            } else {
                $ownerAccountUserOfThisAccount = $accountUser->account->accountUsers()->whereHas('roles', function($query){
                    $query->where('name', 'owner');
                })->first();
                $ownerUserOfThisAccount = $ownerAccountUserOfThisAccount ? $ownerAccountUserOfThisAccount->user : null;
            }

            if(!$ownerUserOfThisAccount){
                continue;
            }

            $account['user'] = [
                'id' => $ownerUserOfThisAccount->id,
                'name' => $ownerUserOfThisAccount->name,
                'email' => $ownerUserOfThisAccount->email,
            ];
            $accessibleAccounts[] = $account;
        }

        return $accessibleAccounts;
    }

    public function getOwnerAccount(){
        $user = $this;
        $accountUserOwner = $user->accountUsers()->whereHas('roles', function($query){
            $query->where('name', 'owner');
        })->first();

        return $accountUserOwner ? $accountUserOwner->account : null;
    }

    public function getIsOwnerAccountLockedAttribute(){
        $user = $this;
        $accountOwner = $user->getOwnerAccount();

        return $accountOwner && $accountOwner->is_locked;
    }
}
