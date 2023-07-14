<?php

namespace App\Mail\Notifications;

use App\Models\User;
use App\Mail\BaseSubscriberNotification;

class ResetPasswordNotification extends BaseSubscriberNotification
{
    public $buttonText = 'Reset password';
    public $includeHavingTroubleText = true;
    public $subject = 'Reset your password';
    public $view = 'email.reset-password';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token = '', User $user = null)
    {
        parent::__construct($user);
        $this->buttonHref = config('app.url') . "/user/reset-password?" . $token;
    }
}
