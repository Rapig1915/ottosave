<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionResource extends JsonResource
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
            'account_id' => $resource->account_id,
            'name' => $resource->name,
            'type' => $resource->type,
            'oauth_transition_message' => ($resource->credentials && $resource->credentials->finicity_oauth_institution) ? $resource->credentials->finicity_oauth_institution->transition_message : ''
        ];
    }
}
