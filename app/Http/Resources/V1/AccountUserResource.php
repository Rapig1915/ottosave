<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountUserResource extends JsonResource
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
            'account_id' => $resource->account_id,
            'all_permission_names' => $resource->all_permission_names,
            'all_role_names' => $resource->all_role_names,
            'user_id' => $resource->user_id,
        ];
    }
}
