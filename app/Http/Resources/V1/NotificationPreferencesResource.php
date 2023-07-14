<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferencesResource extends JsonResource
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
            'account_user_id' => $resource->account_user_id,
            'assignment_reminder_frequency' => $resource->assignment_reminder_frequency,
            'defense_reminder_frequency' => $resource->defense_reminder_frequency,
            'transfer_warning_modal_dismissed' => $resource->transfer_warning_modal_dismissed,
        ];
    }
}
