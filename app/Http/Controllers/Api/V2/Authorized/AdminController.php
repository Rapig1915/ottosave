<?php

namespace App\Http\Controllers\Api\V2\Authorized;

use App\Events\UserDeleted;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\User;
use App\Http\Resources\V2\AdminUserListResource;
use App\Models\AccountUser;
use Auth;

class AdminController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $pageNum = $request->pageNum ?? 1;
        $perPage = $request->perPage ?? 50;
        $searchString = $request->searchString ?? '';
        $sortBy = $request->sortBy ?? '';
        $sortOrder = $request->sortOrder ?? 'asc';
        
        $query = User::with([
            'accounts',
            'accounts.institutions',
            'accounts.institutions.credentials',
            'accounts.institutions.credentials.finicity_oauth_institution',
            'accounts.finicity_customer',
            'accountUsers',
        ]);

        if(!empty($searchString)){
            $query->where('email', 'LIKE', "%$searchString%");
        }

        if(!empty($sortBy)){
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->paginate($perPage, ['*'], 'page', $pageNum);

        $users->each(function($user) {
            $user->setAppends(['is_owner_account_locked']);
        });

        return response()->json([
            'page' => $users->currentPage(),
            'total' => $users->total(),
            'users' => AdminUserListResource::collection($users)
        ]);
    }

    public function deleteUser(User $user)
    {
        $userAuth = Auth::user();
        if($userAuth->id === $user->id){
            abort(403, 'You can\'t delete current user');
        }

        $user->delete();
        event(new UserDeleted($user));
        return 'OK';
    }

    public function lockUser(User $user, Request $request)
    {
        if(!$request->has('flag')){
            abort(400);
        }

        $shouldLock = $request->flag;
        $userOwnerAccount = $user->getOwnerAccount();
        if($userOwnerAccount){
            $userOwnerAccount->lock($shouldLock);
        }

        return 'OK';
    }

    public function grantUserAccess(User $user, Request $request)
    {
        if(!$request->has('flag')){
            abort(400);
        }

        $userAdmin = Auth::user();
        if($userAdmin->id === $user->id){
            abort(500, "Sorry, this is your own account.");
        }

        $shouldGrantOrRemove = $request->flag;
        $userOwnerAccount = $user->getOwnerAccount();
        if($userOwnerAccount){
            $existingAccountUser = $userAdmin->accountUsers()->where('account_id', $userOwnerAccount->id)->first();
            if(!$shouldGrantOrRemove){
                if($existingAccountUser){
                    $existingAccountUser->delete();
                    return 'Access Removed';
                } else {
                    abort(500, "You don't have access to this account now.");
                }
            } else {
                if($existingAccountUser){
                    abort(500, "You already have access to this account now.");
                } else {
                    $newAccountUser = new AccountUser;
                    $newAccountUser->account_id = $userOwnerAccount->id;
                    $newAccountUser = $userAdmin->accountUsers()->save($newAccountUser);
                    $newAccountUser->assignRole('coach');

                    return 'Access Granted';
                }
            }
        }

        abort(500, "The user doesn't have an account to access.");
    }
}
