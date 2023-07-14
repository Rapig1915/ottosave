<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use \App\Models\NotificationPreferences;
use App\Http\Controllers\Api\V1\Guest\User\Services\InputValidator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\WrappedUserResource;
use App\Http\Resources\V1\NotificationPreferencesResource;

class UserController extends Controller{

    public function getUser(){

        $user = Auth::user();
        $user->current_account_user->load('notificationPreferences');
        return (new WrappedUserResource($user))->response()->setStatusCode(200);
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $payload = $request->all();

        $userIdIncorrect = $payload['id'] !== $authUser->id;
        if ($userIdIncorrect) {
            return response()->json(['message' => 'Permission denied.'], 403);
        }

        $user = User::mergeOrCreate($payload);
        $user->save();
        return new UserResource($user);
    }

    public function changeEmail(Request $request)
    {
        $user = Auth::user();
        $payload = $request->all();
        $this->verifyCurrentPasswordOrError($payload['current_password']);
        $validator = new InputValidator(['email']);
        $validator->validate($payload);
        $user->changeEmail($payload);
        $user->save();
        return response()->json(null, 204);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $payload = $request->all();
        $this->verifyCurrentPasswordOrError($payload['current_password']);
        $validator = new InputValidator(['confirmed_password']);
        $validator->validate($payload);
        $user->changePassword($payload);
        $user->save();
        return response()->json(null, 204);
    }

    protected function verifyCurrentPasswordOrError($currentPassword)
    {
        $user = Auth::user();
        if (!$user->isCurrentPassword($currentPassword)) {
            $statusCode = 403;
            $message = 'Password is incorrect. Please try again.';
            throw new HttpException($statusCode, $message);
        }
    }

    public function getNotificationPreferences()
    {
        $notificationPreferences = Auth::user()->current_account_user->notificationPreferences;
        return new NotificationPreferencesResource($notificationPreferences);
    }

    public function storeNotificationPreferences(Request $request)
    {
        $notificationPreferences = Auth::user()->current_account_user->notificationPreferences;
        $notificationPreferencesPayload = $request->all();
        $userHasNotificationPreferences = !!$notificationPreferences->id;
        $requestIsUpdatingNotificationPreferences = isset($notificationPreferencesPayload['id']);
        $isUpdateUnauthorized = $userHasNotificationPreferences !== $requestIsUpdatingNotificationPreferences || ($requestIsUpdatingNotificationPreferences && $notificationPreferences->id !== $notificationPreferencesPayload['id']);
        if ($isUpdateUnauthorized) {
            throw new HttpException(403, "You do not have access to this Account");
        } else {
            $notificationPreferences = NotificationPreferences::mergeOrCreate($notificationPreferencesPayload);
            Auth::user()->current_account_user->notificationPreferences()->save($notificationPreferences);
            return new NotificationPreferencesResource($notificationPreferences);
        }
    }

    public function resendVerificationEmail(Request $request)
    {
        $user = Auth::user();
        $user->sendVerificationEmail();
        return response()->json(null, 204);
    }
}
