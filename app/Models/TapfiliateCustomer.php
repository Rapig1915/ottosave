<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Services\External\TapfiliateService;

class TapfiliateCustomer extends Model
{
    protected $table = 'tapfiliate_customers';
    protected $fillable = [
        'account_id',
        'customer_id',
        'tapfiliate_id',
        'referral_code'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($tapfiliateCustomer) {
            $tapfiliateCustomer->deleteTapfiliateCustomer();
        });
    }

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function deleteTapfiliateCustomer()
    {
        $tapfiliateCustomer = $this;
        $tapfiliateService = new TapfiliateService();
        $tapfiliateService->deleteCustomer($tapfiliateCustomer->tapfiliate_id);
    }
}
