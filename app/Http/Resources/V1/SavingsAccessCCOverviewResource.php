<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\InstitutionAccountOverviewResource;
use App\Http\Resources\V1\TransactionResource;

class SavingsAccessCCOverviewResource extends JsonResource
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
            'name' => $resource->name,
            'slug' => $resource->slug,
            'type' => $resource->type,
            'color' => $resource->color,
            'icon' => $resource->icon,
            'online_banking_url' => $resource->online_banking_url,
            'is_required' => $resource->is_required,
            'is_balance_overridden' => (bool)$resource->is_balance_overridden,
            'allocation_balance_adjustment' => (float)$resource->allocation_balance_adjustment,
            'assignment_balance_adjustment' => (float)$resource->assignment_balance_adjustment,
            'balance_available' => (float)$resource->balance_available,
            'balance_current' => (float)$resource->balance_current,
            'balance_limit' => (float)$resource->balance_limit,
            'count_of_unassigned_transactions' => $resource->count_of_unassigned_transactions,
            'institution_account' => new InstitutionAccountOverviewResource($resource->institutionAccount),
            'unassigned_transactions' => TransactionResource::collection($resource->unassignedTransactions),
        ];
    }
}
