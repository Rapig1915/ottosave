<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V2\AdminUserListAccountResource;

class AdminUserListResource extends JsonResource
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
            'name' => $resource->name,
            'email' => $resource->email,
            'email_verified' => $resource->email_verified,
            'email_verification_token' => $resource->email_verification_token,
            'verification_requested_at' => $resource->verification_requested_at,
            'accounts' => AdminUserListAccountResource::collection($resource->accounts),
            'is_owner_account_locked' => $resource->is_owner_account_locked ?? false,
            'account_users' => $resource->accountUsers
        ];
    }
}
