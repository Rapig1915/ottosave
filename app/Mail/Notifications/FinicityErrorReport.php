<?php

namespace App\Mail\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Institution;
use App\Models\Account;
use App\Models\User;

class FinicityErrorReport extends Mailable
{
    use Queueable, SerializesModels;
    public $institution;
    public $finicityAccount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Institution $institution = null, $finicityAccount = null)
    {
        if (!$institution) {
            // Use dummy content for previewing email
            $this->institution = new \stdClass();
            $this->institution->id = 0;
            $this->institution->account = new \stdClass();
            $this->institution->account->id = 0;
            $this->institution->account->users = [];
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Jane Doe';
            $user->email = 'test@test.com';
            $this->institution->account->users[] = $user;
            $finicityAccount = '{"id": "3203", "name": "Visa", "type": "creditCard", "status": "active", "aggregationStatusCode": "185", "customerId": "41442", "institutionId": "101732", "balanceDate": "1418022000", "aggregationSuccessDate": "1421996400", "aggregationAttemptDate": "1421996400", "createdDate": "1418080904", "lastUpdatedDate": "1422467353", "institutionLoginId": "17478973"}';
            $this->finicityAccount = json_decode($finicityAccount);
        } else {
            $this->institution = $institution;
            $this->finicityAccount = $finicityAccount;
            unset($this->finicityAccount->number);
            unset($this->finicityAccount->details);
            unset($this->finicityAccount->balance);
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Finicity Institution Error')->view('email.reports.finicity-error');
    }
}
