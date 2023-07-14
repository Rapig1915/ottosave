<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class FinicityConnectLinkResource extends JsonResource
{
    public $link;

    public function __construct($link)
    {
        $resource = $this;
        $resource->link = $link;
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
            'connect_link' => $resource->link
        ];
    }
}
