<?php

namespace App\Mail\Notifications;

use App\Models\User;
use App\Models\Account;
use Carbon\Carbon;
use App\Mail\BaseSubscriberNotification;

class TrialEndingNotification extends BaseSubscriberNotification
{
    public $expireDate;
    public $buttonText = 'Add billing information';
    public $subject = 'Your free trial is ending';
    public $view = 'email.reminders.trial-ending';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user = null, Account $account = null)
    {
        parent::__construct($user);

        if (!$account) {
            $this->expireDate = Carbon::now()->addDays(7)->format('m-d-Y');
        } else {
            $this->expireDate = Carbon::parse($account->expire_date)->format('m-d-Y');
        }
        $this->buttonHref = config('app.url') . '/settings';
    }
}
