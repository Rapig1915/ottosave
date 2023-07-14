<?php

namespace App\Http\Controllers\Api\V1\Guest\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller{


    public function __construct()
    {
    }

    public function resetPassword(Request $request){
        $resetPasswordController = $this;
        $resetPasswordController->validate($request, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*[0-9])/',
        ], [
            'password.regex' => 'The password must include both a letter and a number.'
        ]);

        $credentials = $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );

        $response = Password::broker()->reset(
            $credentials, function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if($response === Password::PASSWORD_RESET){
            return response()->json(null, 204);
        } else {
            return response()->json(['message' => 'We weren\'t able to reset your password.'], 500);
        }
    }
}
