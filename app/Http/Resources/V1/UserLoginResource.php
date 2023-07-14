<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\RefreshTokenResource;

class UserLoginResource extends JsonResource
{
    public $user;
    public $token;

    public function __construct($user, $token)
    {
        $resource = $this;
        $resource->user = $user;
        $resource->token = $token;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this;
        return [
            'user' => new UserDetailsResource($resource->user),
            'token' => new RefreshTokenResource($resource->token),
            'accessible_accounts' => $resource->user->getAccessibleAccounts(),
        ];
    }
}
