<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AllocationResource extends JsonResource
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
            'defense_id' => $resource->defense_id,
            'bank_account_id' => $resource->bank_account_id,
            'transferred_from_id' => $resource->transferred_from_id,
            'amount' => $resource->amount,
            'transferred' => $resource->transferred,
            'cleared' => $resource->cleared,
            'cleared_out' => $resource->cleared_out,
            'assignments' => AssignmentResource::collection($resource->whenLoaded('assignments'))
        ];
    }
}
