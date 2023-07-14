<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationViewResource extends JsonResource
{
    public $fileName;
    public function __construct($fileName)
    {
        $resource = $this;
        $resource->fileName = $fileName;
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
        return $resource->fileName;
    }
}
