<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\FinicityOauthMigration;
use App\Services\External\FinicityService;
use GuzzleHttp\Exception\RequestException;

class MigrateFinicityOauthInstitutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $finicityOauthMigration;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(FinicityOauthMigration $finicityOauthMigration)
    {
        $finicityMigrationJob = $this;
        $finicityMigrationJob->finicityOauthMigration = $finicityOauthMigration;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $finicityMigrationJob = $this;
        $finicityOauthMigration = $finicityMigrationJob->finicityOauthMigration;
        $finicityOauthMigration->load([
            'institution_credentials',
            'institution_credentials.institution',
            'institution_credentials.institution.institutionAccount',
            'institution_credentials.institution.account',
            'institution_credentials.institution.account.finicity_customer',
            'finicity_oauth_institution'
        ]);

        $isMigrationComplete = $finicityOauthMigration->status === 'success';
        if ($isMigrationComplete) {
            return true;
        }

        $institutionLoginId = $finicityOauthMigration->institution_credentials->remote_secret;
        $finicityCustomer = $finicityOauthMigration->institution_credentials->institution->account->finicity_customer;

        $FinicityService = new FinicityService();
        $finicityAccounts = $FinicityService->migrateInstitutionLoginIdAccounts($finicityCustomer->customer_id, $institutionLoginId, $finicityOauthMigration->finicity_oauth_institution->new_institution_id);

        $migratedAccounts = $finicityOauthMigration->institution_credentials->institution->institutionAccount;

        foreach ($migratedAccounts as $institutionAccount) {
            $institutionAccount->api_status = 'recoverable';
            $institutionAccount->remote_status_code = 948;
        }
        $finicityOauthMigration->institution_credentials->institution->institutionAccount()->saveMany($migratedAccounts);
        $finicityOauthMigration->institution_credentials->remote_id = $finicityOauthMigration->finicity_oauth_institution->new_institution_id;
        $finicityOauthMigration->institution_credentials->save();
        $finicityOauthMigration->status = 'success';
        $finicityOauthMigration->save();
    }

    public function failed(\Exception $exception)
    {
        $finicityMigrationJob = $this;
        $finicityOauthMigration = $finicityMigrationJob->finicityOauthMigration;
        $finicityOauthMigration->status = 'error';
        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $errorMessage = json_encode($exception->getResponse()->getBody()->getContents());
        } else {
            $errorMessage = $exception->getMessage();
        }
        $finicityOauthMigration->error = $errorMessage;
        $finicityOauthMigration->save();
    }
}
