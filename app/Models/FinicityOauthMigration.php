<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;

class FinicityOauthMigration extends Model
{
    protected $table = 'finicity_oauth_migrations';

    public function institution_credentials()
    {
        return $this->belongsTo('App\Models\InstitutionCredentials');
    }

    public function finicity_oauth_institution()
    {
        return $this->belongsTo('App\Models\FinicityOauthInstitution');
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $finicityOauthMigration = FinicityOauthMigration::findOrFail($payload['id']);
        } else {
            $finicityOauthMigration = new FinicityOauthMigration;
        }
        $finicityOauthMigration->institution_credentials_id = !empty($payload['institution_credentials_id']) ? $payload['institution_credentials_id'] : null;
        $finicityOauthMigration->finicity_oauth_institution_id = !empty($payload['finicity_oauth_institution_id']) ? $payload['finicity_oauth_institution_id'] : null;
        $finicityOauthMigration->status = !empty($payload['status']) ? $payload['status'] : '';
        $finicityOauthMigration->error = !empty($payload['error']) ? $payload['error'] : '';

        return $finicityOauthMigration;
    }
}
