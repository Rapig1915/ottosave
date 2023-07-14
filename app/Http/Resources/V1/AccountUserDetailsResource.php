<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\NotificationPreferencesResource;

class AccountUserDetailsResource extends JsonResource
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
            'user_id' => $resource->user_id,
            'account_id' => $resource->account_id,
            'all_permission_names' => $resource->all_permission_names,
            'all_role_names' => $resource->all_role_names,
            'notification_preferences' => new NotificationPreferencesResource($resource->notificationPreferences)
        ];
    }
}
