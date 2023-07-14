<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Institution;

class InstitutionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $account;

    public function __construct(Institution $institution)
    {
        $this->account = $institution->account;
    }
}
