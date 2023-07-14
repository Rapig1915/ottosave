<?php

namespace App\Mail\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;
use App\Mail\BaseSubscriberNotification;

class AssignChargesReminder extends BaseSubscriberNotification
{
    public $buttonText = 'Log in';
    public $subject = 'Assign your credit card charges';
    public $view = 'email.reminders.assign-charges';
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user = null)
    {
        parent::__construct($user);
        $this->buttonHref = config('app.url') . '/credit-card';
    }

}
