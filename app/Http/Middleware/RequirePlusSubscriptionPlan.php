<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RequirePlusSubscriptionPlan
{
    public function handle($request, Closure $next)
    {
        $currentAccount = Auth::user()->current_account;
        $isPlusSubscriber = $currentAccount->subscription_plan === 'plus';
        $isSubscriptionCurrent = $currentAccount->status && $currentAccount->status !== 'expired';

        if(!$isPlusSubscriber){
            return response()->json(['message' => 'This operation requires a subscription to Otto'], 403);
        } elseif (!$isSubscriptionCurrent) {
            return response()->json(['message' => 'This operation requires a current subscription to Otto'], 403);
        }

        return $next($request);
    }
}
