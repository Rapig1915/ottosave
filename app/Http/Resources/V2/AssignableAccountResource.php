<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V2\InstitutionAccountResource;
use App\Http\Resources\V1\AssignmentResource;

class AssignableAccountResource extends JsonResource
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
            'institution_account_id' => $resource->institution_account_id,
            'parent_bank_account_id' => $resource->parent_bank_account_id,
            'name' => $resource->name,
            'slug' => $resource->slug,
            'type' => $resource->type,
            'color' => $resource->color,
            'icon' => $resource->icon,
            'is_required' => $resource->is_required,
            'is_balance_overridden' => (bool)$resource->is_balance_overridden,
            'online_banking_url' => $resource->online_banking_url,
            'balance_current' => (float)$resource->balance_current,
            'balance_available' => (float)$resource->balance_available,
            'allocation_balance_adjustment' => (float)$resource->allocation_balance_adjustment,
            'assignment_balance_adjustment' => (float)$resource->assignment_balance_adjustment,
            'institution_account' => new InstitutionAccountResource($resource->institutionAccount),
            'untransferred_assignments' => AssignmentResource::collection($resource->untransferredAssignments),
        ];;
    }
}
