<?php

namespace App\Mail\Notifications;

use App\Models\User;
use App\Mail\BaseSubscriberNotification;

class ExpiredAccountNotification extends BaseSubscriberNotification
{
    public $buttonText = 'Update billing information';
    public $subject = 'Update your billing information';
    public $view = 'email.reminders.expired-account';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user = null)
    {
        parent::__construct($user);
        $this->buttonHref = config('app.url') . '/settings';
    }
}
