<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {
        $accountUser = Auth::user()->current_account_user;
        $hasPermission = $accountUser->hasPermissionTo($permission);
        if (!$hasPermission) {
            abort(403, 'The "' . $permission . '" permission is required to access this feature, contact your administrator to request access.');
        }

        return $next($request);
    }
}
