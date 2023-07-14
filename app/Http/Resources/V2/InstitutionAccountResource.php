<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V2\InstitutionResource;
use Carbon\Carbon;

class InstitutionAccountResource extends JsonResource
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
            'linked_at' => $resource->linked_at ? Carbon::parse($resource->linked_at)->format('Y-m-d H:i:sP') : null,
            'mask' => $resource->mask,
            'subtype' => $resource->subtype,
            'name' => $resource->name,
            'balance_available' => (float)$resource->balance_available,
            'balance_current' => (float)$resource->balance_current,
            'balance_limit' => (float)$resource->balance_limit,
            'institution_id' => $resource->institution_id,
            'iso_currency_code' => $resource->iso_currency_code,
            'remote_id' => $resource->remote_id,
            'remote_status_code' => $resource->remote_status_code,
            'api_status' => $resource->api_status,
            'api_status_message' => $resource->api_status_message,
            'institution' => new InstitutionResource($resource->institution),
        ];
    }
}
