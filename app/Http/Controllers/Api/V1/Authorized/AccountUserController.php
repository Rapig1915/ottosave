<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WrappedUserResource;
use App\Mail\Notifications\AccountInviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\AccountInvite;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use DB;

class AccountUserController extends Controller
{
    public function getAccountUsers()
    {
        $account_id = request()->route('account_id');
        $accountUsers = AccountUser::with('user')->where('account_id', $account_id)->get();
        foreach ($accountUsers as $accountUser) {
            $accountUser->setAppends(['all_role_names']);
        }
        return response()->json($accountUsers);
    }

    public function deleteAccountUser()
    {
        $account_user_id = request()->route('account_user_id');

        $authUser = Auth::user();
        $authAccountUser = $authUser->current_account_user;
        $isDeletingSelf = $authAccountUser->id == $account_user_id;
        if($isDeletingSelf) {
            abort(403, 'Cannot delete your own account.');
        }

        $accountUser = AccountUser::findOrFail($account_user_id);
        $accountUser->delete();

        return response()->json(null, 204);
    }

    public function createInvite(Request $request)
    {
        $validationRules = [
            'name' => 'required',
            'email' => 'email|required',
            'all_role_names' => 'array|required'
        ];
        $validationMessages = ['all_role_names.required' => 'Please select at least one role.'];
        $validator = Validator::make($request->all(), $validationRules, $validationMessages);
        $validator->validate();
        $accountId = request()->route('account_id');
        $roleNames = $request->input('all_role_names');
        $name = $request->input('name');
        $email = $request->input('email');
        $roleIds = Role::whereIn('name', $roleNames)->select('id')->get()->pluck('id')->all();

        try {
            DB::beginTransaction();
            $accountInvite = AccountInvite::inviteUserToAccount($accountId, $name, $email, $roleIds);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollback();
            abort(500, 'Failed to invite account user.');
        }

        Mail::to($accountInvite->email)->queue(new AccountInviteNotification($accountInvite));

        $accountInvite->load('roles');
        return response()->json($accountInvite);
    }

    public function listInvites(Request $request)
    {
        $accountInvites = Auth::user()->current_account->accountInvites()->with('roles')->get();
        return response()->json($accountInvites);
    }

    public function deleteAccountInvite()
    {
        $accountInviteId = request()->route('account_invite_id');
        $accountInvite = AccountInvite::findOrFail($accountInviteId);
        $accountInvite->delete();
        return response()->json(null, 204);
    }

    public function acceptAccountInvite(Request $request)
    {
        $user = auth()->user();
        try {
            DB::beginTransaction();
            Account::addInvitedUser($user, $request->input('invite_code'));
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollback();
            throw $exception;
        }
        $user->setAppends([
            'current_account',
            'current_account_user'
        ]);
        $user->current_account_user->load('notificationPreferences');
        return (new WrappedUserResource($user))->response()->setStatusCode(200);
    }

    public function resendAccountInvite()
    {
        $accountInviteId = request()->route('account_invite_id');
        $accountInvite = AccountInvite::findOrFail($accountInviteId);

        Mail::to($accountInvite->email)->queue(new AccountInviteNotification($accountInvite));

        $accountInvite->touch();
        return response()->json($accountInvite, 200);
    }
}
