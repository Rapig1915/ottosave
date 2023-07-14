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

class AccountCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $account;
    public $referralCode;

    public function __construct(Account $account, $referralCode)
    {
        $this->account = $account;
        $this->referralCode = $referralCode;
    }
}
