<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;

abstract class BaseSubscriberNotification extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $userName;
    public $buttonText = 'Verify email';
    public $buttonHref;
    public $includeHavingTroubleText = false;
    public $previewText = '';
    public $subject = '';
    public $view = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user = null)
    {
        if (!$user) {
            // Use dummy content for previewing email
            $this->user = new User();
            $this->user->name = 'Jane Doe';
            $this->user->email = 'jane_doe_tester@buink.biz';
            $this->user->email_verification_token = bin2hex(random_bytes(16));
        } else {
            $this->user = $user;
        }
        $this->userName = explode(' ', $this->user->name)[0] ?? '';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)->view($this->view);
    }
}
