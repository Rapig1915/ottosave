<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public $fillable = [
        'braintree_customer_id',
        'token',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'braintree_customer_id', 'braintree_customer_id');
    }

    public function setIsDefaultAttribute($isDefault)
    {
        $paymentMethod = $this;
        if ($isDefault && $paymentMethod->account) {
            $paymentMethod->account->paymentMethods()->where('id', '!=', $paymentMethod->id)->update(['is_default' => false]);
        }
        $paymentMethod->attributes['is_default'] = $isDefault;
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $paymentMethod = PaymentMethod::findOrFail($payload['id']);
        } else {
            $paymentMethod = new PaymentMethod;
        }
        return $paymentMethod;
    }

}
