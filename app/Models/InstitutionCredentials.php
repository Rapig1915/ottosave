<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionCredentials extends Model
{
    protected $hidden = ['remote_id','remote_secret'];
    protected $fillable = ['remote_id','remote_secret'];

    public function institution()
    {
        return $this->belongsTo('App\Models\Institution');
    }

    public function finicity_oauth_migration()
    {
        return $this->hasOne('App\Models\FinicityOauthMigration');
    }

    public function finicity_oauth_institution()
    {
        return $this->belongsTo('App\Models\FinicityOauthInstitution', 'remote_id', 'old_institution_id');
    }

    public function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $institutionCredentials = InstitutionCredentials::findOrFail($payload['id']);
        } else {
            $institutionCredentials = new InstitutionCredentials;
        }

        return $institutionCredentials;
    }
}
