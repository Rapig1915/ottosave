<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleItemResource extends JsonResource
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
            'date_end' => $resource->date_end,
            'approximate_due_date' => $resource->approximate_due_date,
            'amount_monthly' => (float)$resource->amount_monthly,
            'amount_total' => (float)$resource->amount_total,
            'description' => $resource->description,
            'type' => $resource->type,
        ];
    }
}
