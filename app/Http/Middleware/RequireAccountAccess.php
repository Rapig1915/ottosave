<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RequireAccountAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $accountId = (int)$request->route('account_id');

        if($accountId === 0) {
            return response()->json(['message' => 'Invalid account.'], 403);
        }

        $user = Auth::user();
        $userAccountIds = $user->accounts->pluck('id')->toArray();
        $hasAccountAccess = in_array($accountId, $userAccountIds);

        if(!$hasAccountAccess){
            return response()->json(['message' => 'You don\'t have access to this account.'], 403);
        }
        
        $accountUser = $user->accountUsers()->where('account_id', $accountId)->first();
        $allRoleNames = $accountUser->all_role_names ?? [];
        $hasAnyRole = count($allRoleNames) > 0 && (in_array('coach', $allRoleNames) || in_array('owner', $allRoleNames));
        if(!$hasAnyRole){
            return response()->json(['message' => 'Your account exists but you don\'t have any role to this account.'], 403);
        }

        return $next($request);
    }
}
