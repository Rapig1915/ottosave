<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Account;

class AccountStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $account;
    public $oldStatus;
    public $newStatus;

    public function __construct(Account $account, $oldStatus = '', $newStatus = '')
    {
        $this->account = $account;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
