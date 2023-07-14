<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;
use App\Models\FinicityOauthMigration;
use App\Jobs\MigrateFinicityOauthInstitutionJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinicityOauthInstitution extends Model
{
    use HasFactory;
    
    protected $table = 'finicity_oauth_institutions';
    protected $fillable = [
        'old_institution_id',
        'new_institution_id',
        'transition_message'
    ];

    public function institution_credentials()
    {
        return $this->hasMany('App\Models\InstitutionCredentials', 'remote_id', 'old_institution_id');
    }

    public function successful_migrations()
    {
        return $this->hasMany('App\Models\FinicityOauthMigration')->where('status', '=', 'success');
    }

    public function failed_migrations()
    {
        return $this->hasMany('App\Models\FinicityOauthMigration')->where('status', '=', 'error');
    }

    public function pending_migrations()
    {
        return $this->hasMany('App\Models\FinicityOauthMigration')->where('status', '=', 'pending');
    }

    public function getNumberOfInstitutionsToMigrateAttribute()
    {
        return count($this->institution_credentials);
    }

    public function getNumberOfSuccessfulMigrationsAttribute()
    {
        return count($this->successful_migrations);
    }

    public function getNumberOfFailedMigrationsAttribute()
    {
        return count($this->failed_migrations);
    }

    public function getNumberOfPendingMigrationsAttribute()
    {
        return count($this->pending_migrations);
    }

    public function appendResourceProperties()
    {
        $finicityOauthInstitution = $this;
        $finicityOauthInstitution->loadMissing([
            'successful_migrations',
            'failed_migrations',
            'pending_migrations',
            'institution_credentials'
        ]);
        $finicityOauthInstitution->setAppends([
            'number_of_institutions_to_migrate',
            'number_of_successful_migrations',
            'number_of_failed_migrations',
            'number_of_pending_migrations'
        ]);
    }

    public function migrateRemainingUsers()
    {
        $finicityOauthInstitution = $this;
        $finicityOauthInstitution->loadMissing([
            'pending_migrations',
            'institution_credentials',
            'institution_credentials.finicity_oauth_migration'
        ]);
        $credentialsToMigrate = $finicityOauthInstitution->institution_credentials;
        $pendingMigrationsKeyedByCredentialId = $finicityOauthInstitution->pending_migrations->keyBy('institution_credentials_id');

        foreach ($credentialsToMigrate as $institutionCredentials) {
            $pendingMigrationExists = isset($pendingMigrationsKeyedByCredentialId[$institutionCredentials->id]);
            if (!$pendingMigrationExists) {
                $finicityOauthInstitution->dispatchMigration($institutionCredentials);
            }
        }
    }

    public function dispatchMigration($institutionCredentials, $synchronous = false)
    {
        $finicityOauthInstitution = $this;
        $migrationPayload = [
            'institution_credentials_id' => $institutionCredentials->id,
            'finicity_oauth_institution_id' => $finicityOauthInstitution->id,
            'status' => 'pending'
        ];
        $previousMigration = $institutionCredentials->finicity_oauth_migration;
        if ($previousMigration) {
            $migrationPayload['id'] = $previousMigration->id;
        }
        $finicityOauthMigration = FinicityOauthMigration::mergeOrCreate($migrationPayload);
        $finicityOauthMigration->save();
        if ($synchronous) {
            MigrateFinicityOauthInstitutionJob::dispatchSync($finicityOauthMigration);
        } else {
            MigrateFinicityOauthInstitutionJob::dispatch($finicityOauthMigration);
        }
    }

    public function merge($payload)
    {
        $finicityOauthInstitution = $this;
        $finicityOauthInstitution->old_institution_id = $payload['old_institution_id'];
        $finicityOauthInstitution->new_institution_id = $payload['new_institution_id'];
        $finicityOauthInstitution->transition_message = $payload['transition_message'] ?? 'We have upgraded our connection to your institution, please re-enter your credentials.';
    }
}
