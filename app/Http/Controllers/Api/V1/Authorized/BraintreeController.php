<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BraintreeTokenResource;

class BraintreeController extends Controller
{
    public function getClientAuthToken()
    {
        $user = Auth::user();
        $braintreeCustomerId = $user->currentAccount->braintree_customer_id ?? null;
        $braintreeService = new \App\Services\External\BraintreeService();
        $token = $braintreeService->getClientToken($braintreeCustomerId);
        $merchantId = config('services.braintree.merchant_id');
        return new BraintreeTokenResource($token, $merchantId);
    }
}
