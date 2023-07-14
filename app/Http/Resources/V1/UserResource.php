<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\AccountResource;
use App\Http\Resources\V1\AccountUserResource;

class UserResource extends JsonResource
{
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
            'id' => $resource->id,
            'created_at' => $resource->created_at ? $resource->created_at->format('Y-m-d H:i:sP') : null,
            'updated_at' => $resource->updated_at ? $resource->updated_at->format('Y-m-d H:i:sP') : null,
            'email' => $resource->email,
            'name' => $resource->name,
            'email_verification_token' => $resource->email_verification_token,
            'email_verified' => $resource->email_verified,
            'current_account' => new AccountResource($resource->current_account),
            'current_account_user' => new AccountUserResource($resource->current_account_user)
        ];
    }
}
