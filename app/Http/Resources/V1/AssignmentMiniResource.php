<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\TransactionResource;
use App\Http\Resources\V1\AllocationResource;

class AssignmentMiniResource extends JsonResource
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
            'bank_account_id' => $resource->bank_account_id,
            'transaction' => $resource->transaction ? [
                'remote_transaction_date' => $resource->transaction->remote_transaction_date,
                'amount' => $resource->transaction->amount,
            ] : null,
        ];
    }
}
