<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'bank_account_id' => $resource->bank_account_id,
            'allocation_id' => $resource->allocation_id,
            'amount' => (float)$resource->amount,
            'is_assignable' => $resource->is_assignable,
            'action_type' => $resource->action_type,
            'merchant' => $resource->merchant,
            'remote_account_id' => $resource->remote_account_id,
            'remote_category' => $resource->remote_category,
            'remote_category_id' => $resource->remote_category_id,
            'remote_merchant' => $resource->remote_merchant,
            'remote_transaction_date' => $resource->remote_transaction_date ? $resource->remote_transaction_date->format('Y-m-d H:i:s') : null,
            'remote_transaction_id' => $resource->remote_transaction_id,
            'parent_transaction_id' => $resource->parent_transaction_id,
        ];
    }
}
