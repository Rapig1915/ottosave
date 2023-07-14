<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\AllocationResource;

class MostRecentDefenseResource extends JsonResource
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
            'end_date' => $resource->end_date,
            'account_id' => $resource->account_id,
            'allocated' => $resource->allocated,
            'everyday_checking_starting_balance' => (float)$resource->spending_account_starting_balance,
            'is_current' => $resource->is_current,
            'allocation' => AllocationResource::collection($resource->allocation),
        ];
    }
}
