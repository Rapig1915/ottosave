<?php

namespace App\Http\Controllers\Api\V1\External;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\ITunesReceipt;
use App\Jobs\RenewAccountSubscriptionJob;
use App\Http\Resources\V1\IosProductResource;

class IosController extends Controller
{
    public function getIosSubscriptionProducts()
    {
        $currentAccount = Auth::user()->current_account;
        $iosProducts = $currentAccount->getIosSubscriptionProducts();
        return IosProductResource::collection(collect($iosProducts));
    }

    public function verifyItunesSubscriptionReceipt()
    {
        $currentAccount = Auth::user()->current_account;
        $encodedReceipt = request('receipt');
        $iTunesReceipt = ITunesReceipt::verifySubscriptionReceipt($encodedReceipt, $currentAccount->id);
        $currentAccount->updateSubscriptionFromITunesReceipt($iTunesReceipt);
        $currentAccount->save();
        return response()->json(null, 204);
    }

    public function handleSubscriptionStatusNotification(Request $request)
    {
        $iosController = $this;
        try {
            $notificationType = $request->input('notification_type');
            switch ($notificationType) {
                case 'INITIAL_BUY':
                    // no action necessary, receipt should be stored and verified at purchase time
                    break;
                case 'CANCEL':
                    $iosController->handleCancellationNotification($request);
                    break;
                case 'RENEWAL':
                    $iosController->handleRenewalNotification($request);
                    break;
                case 'INTERACTIVE_RENEWAL':
                    $iosController->handleRenewalNotification($request);
                    break;
                case 'DID_CHANGE_RENEWAL_PREF':
                    $iosController->handleRenewalPreferenceNotification($request);
                    break;
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $shouldAppleReattemptNotification = $errorMessage !== 'Subscription Expired';
            if ($shouldAppleReattemptNotification) {
                return response()->json(null, 500);
            }
        }

        return response()->json(null, 204);
    }

    private function handleCancellationNotification(Request $request)
    {
        $transactionId = $request->input('original_transaction_id');
        $iTunesReceipt = ITunesReceipt::with('account')->where('transaction_id', $transactionId)->first();
        $account = $iTunesReceipt->account;
        $account->status = 'grace';
        $account->save();
    }

    private function handleRenewalNotification(Request $request)
    {
        $transactionId = $request->input('original_transaction_id');
        $iTunesReceipt = ITunesReceipt::with('account')->where('transaction_id', $transactionId)->first();
        $account = $iTunesReceipt->account;
        RenewAccountSubscriptionJob::dispatch($account);
    }
    private function handleRenewalPreferenceNotification(Request $request)
    {
        $iosController = $this;
        if ($request->input('auto_renew_status') === 'true') {
            $iosController->handleRenewalNotification($request);
        } else {
            $iosController->handleCancellationNotification($request);
        }
    }

    public function getAppUniversalLinkJson()
    {
        $path = storage_path('app/public/apple-app-site-association');
        return response()->file($path, ['Content-Type' => 'application/json']);
    }
}
