<?php

namespace App\Mail\Notifications;

use App\Mail\BaseSubscriberNotification;
use App\Models\AccountInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AccountInviteNotification extends Mailable
{
    public $accountInvite;
    public $inviteLink;
    public $inviterName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(AccountInvite $accountInvite)
    {
        $this->accountInvite = $accountInvite;
        $this->inviterName = $accountInvite->getInviter()->name;
        $this->inviteLink = config('app.url') . '/settings/accept-invite?invite_code=' . $accountInvite->invite_code . '&account=' . urlencode($this->inviterName);
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("You've been invited to access an account on Otto")->view('email.invite');
    }
}
