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

class AccountSubscriptionPlanUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $account;
    public $oldPlan;
    public $newPlan;

    public function __construct(Account $account, $oldPlan = '', $newPlan = '')
    {
        $this->account = $account;
        $this->oldPlan = $oldPlan;
        $this->newPlan = $newPlan;
    }
}
