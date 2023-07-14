<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Mail\Notifications\InstitutionErrorNotification;
use \App\Models\Institution;
use \App\Models\SentEmail;
use Illuminate\Support\Facades\Mail;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class SendInstitutionErrorNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $institution;

    public function __construct(Institution $institution)
    {
        $institutionErrorJob = $this;
        $institutionErrorJob->institution = $institution;
    }
    public function handle()
    {
        $institutionErrorJob = $this;
        $jobKey = 'institution_error_notification_' . $institutionErrorJob->institution->id;
        Redis::funnel($jobKey)->limit(1)->then(function () use ($institutionErrorJob, $jobKey) {
            $oneWeekAgo = Carbon::now()->subWeeks(1);
            $recoverableAccount = $institutionErrorJob->institution->institutionAccount()->whereHas('bankAccount')->where('api_status', '=', 'recoverable')->first();
            $recentlySentEmails = SentEmail::where('email_identifier', $jobKey)->whereDate('send_date', '>', $oneWeekAgo)->get();
            $shouldSendNotifications = $recoverableAccount && count($recentlySentEmails) === 0;

            if ($shouldSendNotifications) {
                foreach ($institutionErrorJob->institution->account->accountUsers as $accountUser) {
                    if ($accountUser->user->email_verified) {
                        Mail::to($accountUser->user)->queue(new InstitutionErrorNotification($accountUser->user, $institutionErrorJob->institution, $recoverableAccount->bankAccount));
                        SentEmail::create([
                            'account_user_id' => $accountUser->id,
                            'email_identifier' => $jobKey,
                            'send_date' => Carbon::now()
                        ]);
                    }
                }
            }
        }, function () {
            return true;
        });
    }
}
