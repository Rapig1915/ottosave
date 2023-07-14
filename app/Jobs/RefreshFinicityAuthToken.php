<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use \App\Models\FinicityToken;
use \App\Models\SentEmail;
use App\Services\External\FinicityService;
use App\Mail\Notifications\FinicityServiceOutageAlert;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class RefreshFinicityAuthToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle()
    {
        $jobKey = 'refresh_finicity_authentication_token';
        Redis::funnel($jobKey)->limit(1)->then(function () {
            $currentAccessToken = FinicityToken::getCurrentToken();
            $isRefreshRequired = !$currentAccessToken || ($currentAccessToken && $currentAccessToken->is_refresh_required);
            if ($isRefreshRequired) {
                $shouldInitializeAccessToken = false;
                $finicityService = new FinicityService($shouldInitializeAccessToken);
                try {
                    $finicityService->generateAccessToken();
                } catch (\Exception $e) {
                    $this->fail($e);
                }
            }
        }, function () {
            return $this->release(20);
        });
    }

    public function failed(\Exception $exception)
    {
        report($exception);
        $twoHoursAgo = Carbon::now()->subHours(2);
        $emailIdentifier = 'finicity_service_outage';
        $recentSentEmail = SentEmail::where('email_identifier', $emailIdentifier)->whereDate('send_date', '>', $twoHoursAgo)->first();
        $isAlertRequried = !$recentSentEmail;

        if ($isAlertRequried) {
            $supportEmailAddress = config('mail.from.address');
            Mail::to($supportEmailAddress)->queue(new FinicityServiceOutageAlert());
            SentEmail::create([
                'email_identifier' => $emailIdentifier,
                'send_date' => Carbon::now()
            ]);
        }
    }
}
