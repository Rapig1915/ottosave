<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\TransactionResource;
use App\Http\Resources\V1\AllocationResource;

class AssignmentResource extends JsonResource
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
            'transaction_id' => $resource->transaction_id,
            'allocated_amount' => $resource->allocated_amount,
            'transferred' => $resource->transferred,
            'transaction' => new TransactionResource($resource->whenLoaded('transaction')),
            'allocations' => AllocationResource::collection($resource->whenLoaded('allocations')),
        ];
    }
}
