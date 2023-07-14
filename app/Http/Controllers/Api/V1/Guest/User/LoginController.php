<?php

namespace App\Http\Controllers\Api\V1\Guest\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Guest\User\Services\PasswordGrantLogin;
use App\Http\Controllers\Api\V1\Guest\User\Services\InputValidator;
use App\Models\User;
use App\Http\Resources\V1\UserLoginResource;
use App\Http\Resources\V1\RefreshTokenResource;
use \App\Events\UserLogin as UserLoginEvent;

class LoginController extends Controller{

    private $passwordGrantLogin;

    public function __construct(PasswordGrantLogin $passwordGrantLogin){
        $loginController = $this;
        $loginController->passwordGrantLogin = $passwordGrantLogin;
    }

    public function login(Request $request){
        $loginController = $this;
        $payload = $request->all();
        $validator = new InputValidator(['any_email', 'single_password']);
        $validator->validate($payload);
        $email =  $payload['email'];
        $password = $payload['password'];
        $token = $loginController->passwordGrantLogin->attemptLogin($email, $password);
        $user = User::where('email', $email)->first();
        if($user->is_owner_account_locked){
            abort(403, "Account is locked.");
        }

        event(new UserLoginEvent($user));
        return new UserLoginResource($user, $token);
    }
    public function refresh(Request $request){
        $loginController = $this;
        $refreshToken = $loginController->passwordGrantLogin->attemptRefresh();
        return new RefreshTokenResource($refreshToken);
    }
    public function logout(){
        $loginController = $this;
        $loginController->passwordGrantLogin->logout();
        return response()->json(null, 204);
    }
}
