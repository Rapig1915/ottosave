<?php

namespace App\Jobs;

use App\Services\External\ActiveCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUserToActiveCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $syncPayload;
    protected $syncType;
    protected $ActiveCampaignService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($syncType, $syncPayload)
    {
        $syncActiveCampaignJob = $this;
        $syncActiveCampaignJob->syncType = $syncType;
        $syncActiveCampaignJob->syncPayload = $syncPayload;
        $syncActiveCampaignJob->ActiveCampaignService = new ActiveCampaignService();
    }

    

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $syncActiveCampaignJob = $this;
        $numberOfAllowedConcurrentActiveCampaignConnections = 10;
        \Redis::funnel('sync_active_campaign_job')->limit($numberOfAllowedConcurrentActiveCampaignConnections)->then(function () use ($syncActiveCampaignJob) {
            $canSyncUser = !!$syncActiveCampaignJob->syncPayload['user']->last_verified_email;
            if ($canSyncUser) {
                if ($syncActiveCampaignJob->syncType === 'sync_details') {
                    $syncActiveCampaignJob->syncContactDetails();
                } elseif ($syncActiveCampaignJob->syncType === 'update_email') {
                    $syncActiveCampaignJob->updateContactEmail();
                }
            }
        }, function () use ($syncActiveCampaignJob) {
            return $syncActiveCampaignJob->release(60);
        });
    }

    private function syncContactDetails()
    {
        $syncActiveCampaignJob = $this;
        $user = $syncActiveCampaignJob->syncPayload['user'];
        $userAccount = $user->accounts[0];
        /**
         * [-1 => Any, 0 => Unconfirmed, 1 => Active, 2 => Unsubscribed, 3 => Bounced]
         */
        $activeCampaignSubscriptionStatus = ($userAccount && $userAccount->status !== 'deactivated') ? 1 : 2;
        $syncActiveCampaignJob->ActiveCampaignService->syncUserToActiveCampaign($user, $activeCampaignSubscriptionStatus);
    }

    private function updateContactEmail()
    {
        $syncActiveCampaignJob = $this;
        $previousEmail = $syncActiveCampaignJob->syncPayload['previousEmail'];
        $newEmail = $syncActiveCampaignJob->syncPayload['newEmail'];
        $syncActiveCampaignJob->ActiveCampaignService->updateContactEmail($previousEmail, $newEmail);
    }
}
