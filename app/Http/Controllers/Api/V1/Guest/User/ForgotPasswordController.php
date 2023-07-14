<?php

namespace App\Http\Controllers\Api\V1\Guest\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller{


    public function __construct()
    {
    }

    public function requestReset(Request $request){

        $forgotPasswordController = $this;

        $forgotPasswordController->validate($request, ['email' => 'required|email']);
        $forgotPasswordController->validate($request, ['email' => 'required|email']);
        $payload = $request->all();
        $email =  $payload['email'];
        $user = User::where('email', $email)->first();

        if($user){
           $token = Password::createToken($user);
           $user->sendPasswordResetNotification($token);

           return response()->json(null, 204);
        }

        return response()->json(null, 204);
    }
}
