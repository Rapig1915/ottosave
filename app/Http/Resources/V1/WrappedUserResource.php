<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\UserDetailsResource;

class WrappedUserResource extends JsonResource
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
            'user' => new UserDetailsResource($resource),
            'accessible_accounts' => $resource->getAccessibleAccounts(),
        ];
    }
}
