<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class FinicityOauthInstitutionResource extends JsonResource
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
            'old_institution_id' => (string)$resource->old_institution_id,
            'new_institution_id' => (string)$resource->new_institution_id,
            'transition_message' => (string)$resource->transition_message,
            'number_of_institutions_to_migrate' => $resource->number_of_institutions_to_migrate,
            'number_of_successful_migrations' => $resource->number_of_successful_migrations,
            'number_of_failed_migrations' => $resource->number_of_failed_migrations,
            'number_of_pending_migrations' => $resource->number_of_pending_migrations,
        ];
    }
}
