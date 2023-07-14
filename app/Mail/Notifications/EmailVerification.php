<?php

namespace App\Mail\Notifications;

use App\Models\User;
use App\Mail\BaseSubscriberNotification;

class EmailVerification extends BaseSubscriberNotification
{
    public $buttonText = 'Verify email';
    public $includeHavingTroubleText = true;
    public $subject = 'Verify your email';
    public $view = 'email.verify-email';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user = null)
    {
        parent::__construct($user);
        $this->buttonHref = config('app.url') . "/verify?email=" . $this->user->email . "&token=" . $this->user->email_verification_token;
    }
}
