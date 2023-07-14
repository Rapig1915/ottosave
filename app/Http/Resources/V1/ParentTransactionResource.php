<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\SplitTransactionResource;

class ParentTransactionResource extends JsonResource
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
            'bank_account_id' => $resource->bank_account_id,
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
            'split_transactions' => SplitTransactionResource::collection($resource->splitTransactions),
        ];
    }
}
