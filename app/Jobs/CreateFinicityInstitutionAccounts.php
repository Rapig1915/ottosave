<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\FinicityCustomer;
use App\Services\External\FinicityService;
use App\Events\InstitutionAccountCountChanged;
use Illuminate\Support\Facades\Redis;

class CreateFinicityInstitutionAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $finicityCustomer;

    public function __construct(FinicityCustomer $finicityCustomer)
    {
        $this->finicityCustomer = $finicityCustomer;
    }

    public function handle()
    {
        $jobKey = $this->finicityCustomer->customer_id . '_finicity_discovery';
        Redis::funnel($jobKey)->limit(1)->then(function () {
            $wasNewAccountAdded = false;
            $previouslyCreatedInstitutionAccounts = $this->finicityCustomer->account->institutionAccounts->keyBy('remote_id');
            $finicityService = new FinicityService();
            $customerAccounts = $finicityService->getCustomerAccounts($this->finicityCustomer->customer_id);
            $createdBankAccounts = [];
            foreach ($customerAccounts as $customerAccount) {
                $isAccountPreviouslyCreated = isset($previouslyCreatedInstitutionAccounts[$customerAccount->id]);
                if (!$isAccountPreviouslyCreated && $customerAccount->status === 'active') {
                    $bankAccount = $this->finicityCustomer->createLinkedAccount($customerAccount);
                    $createdBankAccounts[] = $bankAccount;
                }
            }
            $wasNewAccountAdded = count($createdBankAccounts) > 0;
            if ($wasNewAccountAdded) {
                event(new InstitutionAccountCountChanged($this->finicityCustomer->account));
            }
            return $createdBankAccounts;
        }, function () {
            return $this->release(30);
        });
    }
}
