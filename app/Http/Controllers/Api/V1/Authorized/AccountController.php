<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use App\Events\AccountDowngraded;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\External\BraintreeService;
use \App\Models\PaymentMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\AccountResource;
use App\Http\Resources\V1\SubscriptionTypeResource;
use App\Models\Account;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function startNewTrial()
    {
        $user = Auth::user();

        $freeTrialAlreadyUsed = $user->currentAccount->is_trial_used;

        if ($freeTrialAlreadyUsed) {
            throw new HttpException(400, "Free Trial already used.");
        } else {
            SubscriptionService::initializeTrialSubscription($user->currentAccount);
            $user->currentAccount->save();
            $couponsToRedeem = $user->currentAccount->redeemed_coupons()->whereNull('used_at')->get();
            foreach ($couponsToRedeem as $coupon) {
                $coupon->redeem($user->currentAccount);
            }
            return new UserResource($user);
        }
    }

    public function startBasicPlan()
    {
        $user = Auth::user();
        $user->currentAccount->status = 'active';
        $user->currentAccount->subscription_plan = 'basic';
        $user->currentAccount->subscription_type = 'monthly';
        $user->currentAccount->save();

        event(new AccountDowngraded($user->currentAccount));
        return new UserResource($user);
    }

    public function cancelSubscription()
    {
        $user = Auth::user();
        $isPlusSubscriber = $user->currentAccount->subscription_plan === 'plus';
        $isPlusSubscriptionActive = $isPlusSubscriber && in_array($user->currentAccount->status, array('active', 'free_trial'));
        $isPlusSubscriptionExpired = $isPlusSubscriber && $user->currentAccount->status === 'expired';
        if (!$isPlusSubscriber) {
            throw new HttpException(400, 'This account does not have an active subscription.');
        } else if ($isPlusSubscriptionExpired) {
            try {
                $user->currentAccount->cleanupSubscriptionItems();
            } catch (\Exception $e) {
                report($e);
                throw new HttpException(500, "Oops, something went wrong while downgrading your account. Please try again.");
            }
        } else if ($isPlusSubscriptionActive) {
            $user->currentAccount->status = 'grace';
            $user->currentAccount->save();

            event(new AccountDowngraded($user->currentAccount));
        }
        return new UserResource($user);
    }

    public function purchaseSubscription()
    {
        $currentAccount = Auth::user()->current_account;
        $nonce = request('paymentNonce');
        $currentAccount->setDefaultPaymentMethod($nonce);
        $currentAccount->refresh();
        $currentAccount->subscription_plan = request('subscriptionPlan');
        $currentAccount->subscription_type = request('subscriptionType');
        $isUpdatingForFutureRenewal = $currentAccount->status === 'grace' || $currentAccount->status === 'free_trial';
        if ($isUpdatingForFutureRenewal) {
            $currentAccount->status = 'active';
            $currentAccount->save();
        } else {
            $currentAccount->renewSubscription();
        }
        return new UserResource(Auth::user());
    }
    
    public function getSubscriptionTypes()
    {
        $subscriptionTypes = collect(Auth::user()->current_account->getSubscriptionTypes());
        return SubscriptionTypeResource::collection($subscriptionTypes);
    }

    public function updateSubscriptionInterval()
    {
        $currentAccount = Auth::user()->current_account;
        $currentAccount->subscription_type = request('subscription_type');
        $currentAccount->save();

        return response()->json(null, 204);
    }

    public function updateSubscriptionPayment()
    {
        $currentAccount = Auth::user()->current_account;
        $nonce = request('paymentNonce');
        $currentAccount->setDefaultPaymentMethod($nonce);
        return new UserResource(Auth::user());
    }

    public function deactivateAccount()
    {
        Auth::user()->current_account->deactivate();
        Auth::user()->current_account_user->notificationPreferences->optOut();
        return response()->json(null, 204);
    }

    public function patch()
    {
        $controller = $this;
        $controller->validate(request(), [
            'projected_defenses_per_month' => 'int|min:1|max:2',
        ]);
        $currentAccount = Auth::user()->current_account;
        $payload = request()->all();
        $patchableProperties = [
            'projected_defenses_per_month'
        ];
        foreach ($patchableProperties as $propertyToUpdate) {
            if (isset($payload[$propertyToUpdate])) {
                $currentAccount->{$propertyToUpdate} = $payload[$propertyToUpdate];
            }
        }
        $currentAccount->save();

        return new AccountResource($currentAccount);
    }

    public function switchAccount(Request $request)
    {
        $accountId = (int)$request->route('account_id');
        $account = Account::findOrFail($accountId);

        return new AccountResource($account);
    }
}
