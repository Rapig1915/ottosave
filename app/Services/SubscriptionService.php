<?php

namespace App\Services;

use App\Services\External\BraintreeService;
use App\Models\Account;
use App\Models\BraintreeTransaction;
use Carbon\Carbon;
use App\Jobs\RenewAccountSubscriptionJob;
use App\Events\BraintreeRenewalComplete;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public $braintreeService;
    public function __construct($braintreeService = null){
        $SubscriptionService = $this;
        if ($braintreeService) {
            $SubscriptionService->braintreeService = $braintreeService;
        } else {
            $SubscriptionService->braintreeService = new BraintreeService();
        }
    }

    public function renewSubscriptionThroughBraintree(Account $account)
    {
        $SubscriptionService = $this;

        $lastBraintreeTransaction = $account->braintree_transactions()->latest()->first();
        $renewalStarted = $lastBraintreeTransaction && $account->getOriginal('status') === 'pending_renewal';
        if ($renewalStarted) {
            $SubscriptionService->updateTransactionStatus($lastBraintreeTransaction);
        } else {
            $SubscriptionService->startBraintreeSubscriptionRenewal($account);
        }

    }

    public function updateTransactionStatus(BraintreeTransaction $braintreeTransaction)
    {
        $SubscriptionService = $this;
        $braintreeResponse = $SubscriptionService->braintreeService->getTransaction($braintreeTransaction->remote_transaction_id);
        $braintreeTransaction->status = $braintreeResponse->status;
        $braintreeTransaction->save();
        $SubscriptionService->continueRenewalFromTransaction($braintreeTransaction);
    }

    public function startBraintreeSubscriptionRenewal(Account $account)
    {
        $SubscriptionService = $this;
        $paymentMethod = $account->paymentMethods()->orderBy('is_default', 'desc')->first();
        $subscriptionCharge = $account->getSubscriptionChargeAmount();
        $activeDiscountCoupon = $account->getActiveDiscountCoupon();

        $braintreeResponse = $SubscriptionService->braintreeService->createSale($subscriptionCharge, 'token', $paymentMethod->token, false);
        $braintreeTransaction = BraintreeTransaction::instantiateFromRemote($braintreeResponse->transaction);
        $braintreeTransaction->subscription_type = $account->subscription_type;
        $braintreeTransaction->coupon_id = $activeDiscountCoupon->id ?? null;
        try {
            DB::beginTransaction();
            $account->incrementExpirationDate($braintreeTransaction->subscription_type);
            $account->status = 'pending_renewal';
            $account->save();
            $account->braintree_transactions()->save($braintreeTransaction);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        $braintreeTransaction->refresh();
        $SubscriptionService->continueRenewalFromTransaction($braintreeTransaction);
    }

    public function continueRenewalFromTransaction(BraintreeTransaction $braintreeTransaction)
    {
        $SubscriptionService = $this;

        switch ($braintreeTransaction->status) {
            case 'authorized':
                $SubscriptionService->submitForSettlement($braintreeTransaction);
                break;
            case 'settling':
            case 'submitted_for_settlement':
            case 'settlement_pending':
                RenewAccountSubscriptionJob::dispatch($braintreeTransaction->account)->delay(now()->addHours(6));
                break;
            case 'settled':
                $SubscriptionService->completeBraintreeRenewal($braintreeTransaction);
                break;
            case 'authorization_expired':
            case 'processor_declined':
            case 'gateway_rejected':
            case 'failed':
            case 'voided':
            case 'settlement_declined':
                $SubscriptionService->handleRenewalFailure($braintreeTransaction);
                break;
            default:
                throw new \Exception("Unexpected BraintreeTransaction status: " . $braintreeTransaction->status);
        }
    }

    public function submitForSettlement(BraintreeTransaction $braintreeTransaction)
    {
        $SubscriptionService = $this;
        $braintreeResponse = $SubscriptionService->braintreeService->submitTransactionForSettlement($braintreeTransaction->remote_transaction_id);
        $braintreeTransaction->status = $braintreeResponse->status;
        $braintreeTransaction->save();
        $SubscriptionService->continueRenewalFromTransaction($braintreeTransaction);
    }

    public function completeBraintreeRenewal(BraintreeTransaction $braintreeTransaction)
    {
        try {
            DB::beginTransaction();
            $isExpirationDateCurrent = !is_null($braintreeTransaction->subscription_type);
            if (!$isExpirationDateCurrent) {
                // incrementing expiration after the transaction settles is deprecated since #629
                $braintreeTransaction->account->incrementExpirationDate($braintreeTransaction->account->subscription_type);
            }
            if ($braintreeTransaction->coupon_id) {
                $subscriptionType = $braintreeTransaction->subscription_type ?? $braintreeTransaction->account->subscription_type;
                $braintreeTransaction->account->consumeDiscountCoupon($braintreeTransaction->coupon_id, $subscriptionType);
            }
            $braintreeTransaction->account->status = 'active';
            $braintreeTransaction->account->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        event(new BraintreeRenewalComplete($braintreeTransaction));
    }

    public function handleRenewalFailure(BraintreeTransaction $braintreeTransaction)
    {
        $braintreeTransaction->account->expire_date = Carbon::now();
        $braintreeTransaction->account->status = 'expired';
        $braintreeTransaction->account->save();
        $braintreeTransaction->account->sendExpirationNotificationToAllUsers();
    }

    public static function initializeTrialSubscription(Account $account, $expirationDateOverride = null)
    {
        if ($account->status === 'demo') {
            $account->bankAccounts()->delete();
            $account->refresh();
            $account->createRequiredBankAccounts();
        }
        if ($expirationDateOverride) {
            $account->expire_date = $expirationDateOverride;
        } else {
            $today = new Carbon();
            $currentExpirationDate = $account->expire_date && Carbon::parse($account->expire_date)->isFuture() ? new Carbon($account->expire_date) : $today;
            $newExpirationDate = $currentExpirationDate->addDays(30);
            $account->expire_date = $newExpirationDate->format('Y-m-d');
        }
        $account->status = 'free_trial';
        $account->is_trial_used = true;
        $account->subscription_plan = 'plus';
        $monthlySubscription = collect($account->getSubscriptionTypes())->where('name', 'Monthly')->where('cleared_for_sale', true)->pluck('slug')->first();
        if ($monthlySubscription) {
            $account->subscription_type = $monthlySubscription;
        } else {
            $firstActiveSubscription = collect($account->getSubscriptionTypes())->where('cleared_for_sale', true)->pluck('slug')->first();
            $account->subscription_type = $firstActiveSubscription;
        }
    }
}
