<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionAccountOverviewResource extends JsonResource
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
            'mask' => $resource->mask,
            'subtype' => $resource->subtype,
            'name' => $resource->name,
            'official_name' => $resource->official_name,
            'balance_available' => (float)$resource->balance_available,
            'balance_current' => (float)$resource->balance_current,
            'balance_limit' => (float)$resource->balance_limit,
            'institution_id' => $resource->institution_id,
            'iso_currency_code' => $resource->iso_currency_code,
            'remote_id' => $resource->remote_id,
        ];
    }
}
