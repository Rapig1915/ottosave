<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\TransactionResource;

class NewlyAssignedTransactionResource extends JsonResource
{
    public $assignment;
    public $balance;
    public function __construct($assignment, $balance)
    {
        $resource = $this;
        $resource->assignment = $assignment;
        $resource->balance = $balance;
    }
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
            'assignment' => [
                'id' => $resource->assignment->id,
                'created_at' => $resource->assignment->created_at ? $resource->assignment->created_at->format('Y-m-d H:i:sP') : null,
                'updated_at' => $resource->assignment->updated_at ? $resource->assignment->updated_at->format('Y-m-d H:i:sP') : null,
                'transaction_id' => $resource->assignment->transaction_id,
                'bank_account_id' => $resource->assignment->bank_account_id,
                'allocated_amount' => (float)$resource->assignment->allocated_amount,
                'transferred' => $resource->assignment->transferred,
                'transaction' => new TransactionResource($resource->assignment->transaction)
            ],
            'updated_balance' => (float)$resource->balance
        ];
    }
}
