<?php

namespace App\Http\Controllers\Api\V1\Guest\User;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Guest\User\Services\PasswordGrantLogin;
use App\Http\Controllers\Api\V1\Guest\User\Services\InputValidator;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\UserLoginResource;
use App\Http\Resources\V1\UserResource;
use App\Events\AccountCreated;
use App\Services\External\ActiveCampaignService;
use App\Jobs\SyncUserToActiveCampaignJob;

class RegisterController extends Controller
{

    public function __construct(PasswordGrantLogin $passwordGrantLogin)
    {
        $registerController = $this;
        $registerController->passwordGrantLogin = $passwordGrantLogin;
    }

    public function register(Request $request)
    {
        abort(Response::HTTP_FORBIDDEN, 'Registration is currently disabled');

        $registerController = $this;

        try {
            DB::beginTransaction();
            $payload = $request->only(['name', 'email', 'password']);
            $validator = new InputValidator(['name', 'email', 'password']);
            $validator->validate($payload);
            $user = User::mergeOrCreate($payload);
            $user->password = $payload['password'];
            $user->email = $payload['email'];
            $user->save();

            // login with payload values since user properties are encrypted
            $email = $payload['email'];
            $password = $payload['password'];
            $token = $registerController->passwordGrantLogin->attemptLogin($email, $password);

            $account = new Account;
            $account->subscription_origin = request()->headers->get('Origin') === 'capacitor://localhost' ? 'ios' : 'web';
            $account = $user->accounts()->save($account);

            $accountUser = $user->accountUsers()->where('account_id', $account->id)->first();
            $accountUser->assignRole('owner');

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollback();
            throw $exception;
        }
        $referralCode = $request->input('referral_code');
        event(new AccountCreated($account, $referralCode));
        $registerController->confirmTestUsers($user);
        if (!$user->email_verified) {
            $user->sendVerificationEmail();
        }
        return (new UserLoginResource($user, $token))->response()->setStatusCode(201);
    }

    public function verifyEmailAddress(Request $request)
    {
        $email = $request->input('email');
        $token = $request->input('token');
        $requestWellFormed = $email && $token;
        $user = User::with('accountUsers')->where('email', $email)->first();
        $emailPreviouslyVerified = $requestWellFormed && $user && $user->email_verified;
        $emailVerified = $requestWellFormed && $user && $user->email_verification_token === $token;
        if ($emailPreviouslyVerified) {
            return new UserResource($user);
        } elseif ($emailVerified) {
            $user->email_verified = true;
            $user->verification_requested_at = null;
            $user->email_verification_token = null;
            $previousEmailAddress = $user->last_verified_email;
            $user->last_verified_email = $user->email;
            $user->save();
            if ($previousEmailAddress) {
                /**
                 * Called service directly rather than via the job
                 * as the call to the job was not updating as expected
                 */
                $activeCampaignService = new ActiveCampaignService();
                $activeCampaignService->updateContactEmail($previousEmailAddress, $user->email);
            } else {
                SyncUserToActiveCampaignJob::dispatch('sync_details', ['user' => $user]);
            }
            return new UserResource($user);
        } else {
            $statusCode = 400;
            $message = 'We were unable to verify your email, please try again.';
            throw new HttpException($statusCode, $message);
        }
    }

    protected function confirmTestUsers(User $user)
    {
        $isTestAccount = preg_match('/^.*@ottosave.com$/', $user->email);
        if ($isTestAccount) {
            $user->email_verified = true;
            $user->last_verified_email = $user->email;
            $user->save();
        }
    }
}
