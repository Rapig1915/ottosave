<?php

namespace App\Mail\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;
use App\Models\Account;

class UserListUpdateReport extends Mailable
{
    use Queueable, SerializesModels;
    public $account;
    public $users = [];
    public $eventType;
    public $oldValue;
    public $newValue;

    public function __construct(Account $account = null, $eventType = '', $oldValue = null, $newValue = '')
    {
        if (!$account) {
            // Use dummy content for previewing email
            $this->account = new Account();
            $this->account->id = 0;
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Jane Doe';
            $user->email = 'test@test.com';
            $this->users[] = $user;
            $this->eventType = 'Account status updated';
            $this->oldValue = 'active';
            $this->newValue = 'expired';
        } else {
            $this->account = $account;
            $this->users = $account->users;
            $this->eventType = $eventType;
            $this->oldValue = $oldValue;
            $this->newValue = $newValue;
        }
    }

    public function build()
    {
        return $this->subject('User List Updated')->view('email.reports.user-list');
    }
}
