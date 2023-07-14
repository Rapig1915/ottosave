<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\AccountUser;
use \App\Mail\Notifications\AssignChargesReminder;
use \App\Mail\Notifications\ExpiredAccountNotification;
use \App\Mail\Notifications\TrialEndingNotification;
use Illuminate\Support\Facades\Mail;
use \Carbon\Carbon;

class SendNotificationEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $sendNotificationEmailsJob = $this;
        AccountUser::with(
            'account',
            'account.defenses',
            'account.savingsAccessCreditCard',
            'account.savingsAccessCreditCard.unassignedTransactions',
            'user',
            'notificationPreferences'
        )->chunk(100, function ($accountUsers) use ($sendNotificationEmailsJob){
            foreach ($accountUsers as $accountUser) {
                $sendNotificationEmailsJob->sendAssignmentReminderEmail($accountUser);
                $sendNotificationEmailsJob->sendAccountExpirationEmail($accountUser);
                $sendNotificationEmailsJob->sendTrialEndingEmail($accountUser);
            }
        });
    }

    private function sendAssignmentReminderEmail(AccountUser $accountUser)
    {
        $today = new Carbon();
        $isMonday = $today->dayOfWeek === 1;
        $hasUnassignedTransactions = $accountUser->account->savingsAccessCreditCard && $accountUser->account->savingsAccessCreditCard->count_of_unassigned_transactions > 0;
        $hasDailyNotifications = $accountUser->notificationPreferences && $accountUser->notificationPreferences->assignment_reminder_frequency === 'daily';
        $hasWeeklyNotifications = $accountUser->notificationPreferences && $accountUser->notificationPreferences->assignment_reminder_frequency === 'weekly';
        $isNotificationRequired = $hasUnassignedTransactions && ($hasDailyNotifications || ($hasWeeklyNotifications && $isMonday));
        if ($isNotificationRequired && $accountUser->user->email_verified && ($accountUser->account->status !== 'demo')) {
            Mail::to($accountUser->user)->queue(new AssignChargesReminder($accountUser->user));
        }
    }

    private function sendAccountExpirationEmail(AccountUser $accountUser)
    {
        if ($accountUser->account->status === 'expired') {
            $oneWeekAgo = Carbon::now()->subWeek();
            $expirationDate = new Carbon($accountUser->account->expire_date);
            $accountExpiredOneWeekAgo = $expirationDate->isSameDay($oneWeekAgo);

            if ($accountExpiredOneWeekAgo && $accountUser->user->email_verified) {
                Mail::to($accountUser->user)->queue(new ExpiredAccountNotification($accountUser->user));
            }
        }
    }

    private function sendTrialEndingEmail(AccountUser $accountUser)
    {
        $isAccountOnTrial = $accountUser->account->status === 'free_trial';
        $isPaymentAvailable = $accountUser->account->subscription_provider === 'itunes' || $accountUser->account->braintree_customer_id;
        $isAccountOnTrialWithoutPaymentOnFile = $isAccountOnTrial && !$isPaymentAvailable;
        if ($isAccountOnTrialWithoutPaymentOnFile) {
            $sevenDaysBeforeExpiration = new Carbon($accountUser->account->expire_date);
            $sevenDaysBeforeExpiration->subDays(7);
            $today = Carbon::now();
            $trialExpiresInSevenDays = $sevenDaysBeforeExpiration->isSameDay($today);

            if ($trialExpiresInSevenDays && $accountUser->user->email_verified) {
                Mail::to($accountUser->user)->queue(new TrialEndingNotification($accountUser->user, $accountUser->account));
            }
        }
    }
}
