<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountInviteResource extends JsonResource
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
            'email' => $resource->email,
            'account_id' => $resource->account_id,
            'all_role_names' => $resource->all_role_names,
            'invite_code' => $resource->invite_code,
            'status' => $resource->status,
            'account' => new AccountResource($resource->whenLoaded('account')),
        ];
    }
}
