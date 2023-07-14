<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;

class FinicityToken extends Model
{
    protected $table = 'finicity_tokens';
    const TOKEN_LIFETIME = 110; // actual lifetime is two hours, we have added a 10 minute buffer for re-authentication
    const TOKEN_REFRESH_LIFETIME = 90; // 90 minutes

    public function getIsRefreshRequiredAttribute()
    {
        $finicityToken = $this;
        $creationTime = new Carbon($finicityToken->created_at);
        $refreshRequiredAt = $creationTime->addMinutes(FinicityToken::TOKEN_REFRESH_LIFETIME);
        $isRefreshRequired = Carbon::now()->greaterThan($refreshRequiredAt);
        return $isRefreshRequired;
    }

    public static function getCurrentToken()
    {
        $expirationTime = Carbon::now()->subMinutes(FinicityToken::TOKEN_LIFETIME);
        return FinicityToken::where('created_at', '>', $expirationTime)->latest()->first();
    }
}
