<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\AssignmentResource;

class SplitTransactionResource extends JsonResource
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
            'amount' => (float)$resource->amount,
            'is_assignable' => $resource->is_assignable,
            'merchant' => $resource->merchant,
            'remote_transaction_date' => $resource->remote_transaction_date ? $resource->remote_transaction_date->format('Y-m-d H:i:s') : null,
            'parent_transaction_id' => $resource->parent_transaction_id,
            'assignment' => new AssignmentResource($resource->assignment)
        ];
    }
}
