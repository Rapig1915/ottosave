<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class RefreshTokenResource extends JsonResource
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
        $tokenResource = [
            'access_token' => $resource['access_token'],
            'expires_in' => $resource['expires_in'],
            'refresh_expires_in' => $resource['refresh_expires_in']
        ];
        if (isset($resource['refresh_token'])) {
            $tokenResource['refresh_token'] = $resource['refresh_token'];
        }
        return $tokenResource;
    }
}
