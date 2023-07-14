<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use \Carbon\Carbon;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;
    
    protected $table = 'coupons';
    protected $dates = [
        'expiration_date',
    ];
    private static $couponTypes = [
        'new_account',
        'upgrade',
        'gift_code',
        'affiliate_code'
    ];
    private static $rewardTypes = [
        'free_month',
        'discount_percentage'
    ];

    public function accounts(){
        return $this->belongsToMany('App\Models\Account')->withPivot('used_at', 'remaining_months');
    }

    public function setTypeSlugAttribute($typeSlug)
    {
        $coupon = $this;
        $allowedSlugs = $coupon::$couponTypes;
        $isSlugValid = in_array($typeSlug, $allowedSlugs);
        if ($isSlugValid) {
            $coupon->attributes['type_slug'] = $typeSlug;
        } else {
            abort(422, "Attempting to set coupon type to invalid value: " . $typeSlug);
        }
    }

    public function setRewardTypeAttribute($rewardType)
    {
        $coupon = $this;
        $allowedSlugs = $coupon::$rewardTypes;
        $isSlugValid = in_array($rewardType, $allowedSlugs);
        if ($isSlugValid) {
            $coupon->attributes['reward_type'] = $rewardType;
        } else {
            abort(422, "Attempting to set coupon type to invalid value: " . $rewardType);
        }
    }

    public function getIsExpiredAttribute()
    {
        $coupon = $this;
        return $coupon->expiration_date ? Carbon::now()->gte($coupon->expiration_date) : false;
    }

    public function getIsRedeemableAttribute()
    {
        $coupon = $this;
        return $coupon->number_of_uses > 0 && !$coupon->is_expired;
    }

    public static function getCouponTypes()
    {
        return Coupon::$couponTypes;
    }

    public static function getRewardTypes()
    {
        return Coupon::$rewardTypes;
    }

    public function redeem(Account $account)
    {
        $coupon = $this;
        switch ($coupon->reward_type) {
            case 'free_month':
                $coupon->applyFreeMonthCoupon($account);
                break;
            case 'discount_percentage':
                $coupon->applyDiscountCoupon($account);
                break;
            default:
                abort(500, 'Attempting to redeem unknown reward type: ' . $coupon->reward_type);
                break;
        }
    }

    public function attachToAccount($account)
    {
        $coupon = $this;
        $coupon->confirmAccountEligibility($account);
        $isUnsupportedRewardType = !in_array($coupon->reward_type, Coupon::getRewardTypes());
        if ($isUnsupportedRewardType) {
            abort(500, 'Coupon has unknown reward type: ' . $coupon->reward_type);
        }
        try {
            DB::beginTransaction();
            $remainingMonths = $coupon->reward_duration_in_months ?? 0;
            $coupon->accounts()->attach($account->id, ['used_at' => null, 'remaining_months' => $remainingMonths]);
            DB::table($coupon->table)->where('id', $coupon->id)->decrement('number_of_uses');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function confirmAccountEligibility($account)
    {
        $coupon = $this;
        $coupon->refresh();
        $isCouponRedeemedForAccount = !!$coupon->accounts()->find($account->id);
        if (!$coupon->is_redeemable) {
            abort(400, 'Coupon expired');
        } elseif ($account->subscription_provider === 'itunes') {
            abort(400, 'Coupon not valid for iTunes subscribers.');
        } elseif ($isCouponRedeemedForAccount) {
            abort(400, 'Coupon already redeemed');
        } elseif ($coupon->type_slug === 'new_account') {
            $isAccountEligible = $account->created_at > $coupon->created_at && !$account->redeemed_coupons()->where('type_slug', 'new_account')->first();
            if (!$isAccountEligible) {
                abort(400, 'Coupon not valid for this account');
            }
        } elseif ($coupon->type_slug === 'upgrade' && $account->subscription_plan === 'plus') {
            abort(400, 'Coupon not valid for this account');
        }
        if ($coupon->reward_type === 'discount_percentage') {
           $isAccountEligible = !$account->getActiveDiscountCoupon();
           if (!$isAccountEligible) {
               abort(400, 'Discount coupon already applied');
           }
       }
        return true;
    }

    private function applyFreeMonthCoupon(Account $account)
    {
        $coupon = $this;
        try {
            DB::beginTransaction();
            $coupon->accounts()->updateExistingPivot($account->id, ['used_at' => Carbon::now()]);
            $newExpirationDate = new Carbon($account->expire_date);
            if ($newExpirationDate->isPast()) {
                $newExpirationDate = new Carbon();
            }
            $newExpirationDate->addMonths($coupon->amount);
            if ($account->subscription_plan === 'basic') {
                SubscriptionService::initializeTrialSubscription($account, $newExpirationDate);
            } else {
                $account->expire_date = $newExpirationDate;
            }
            $account->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function applyDiscountCoupon(Account $account)
    {
        $coupon = $this;
        $remainingMonths = $coupon->reward_duration_in_months ?? 0;
        $coupon->accounts()->updateExistingPivot($account->id, ['used_at' => Carbon::now(), 'remaining_months' => $remainingMonths]);
    }

    public static function createFromPayload($payload)
    {
        Validator::make($payload, [
            'amount' => 'required|numeric',
            'type_slug' => 'required|string',
            'reward_type' => 'required|string',
            'code' => 'string|min:6|unique:coupons',
            'number_of_uses' => 'integer',
            'expiration_date' => 'date|after:today',
            'reward_duration_in_months' => 'integer'
        ])->validate();

        $coupon = new Coupon();
        $coupon->amount = $payload['amount'];
        $coupon->type_slug = $payload['type_slug'];
        $coupon->reward_type = $payload['reward_type'];
        $coupon->code = !empty($payload['code']) ? $payload['code'] : Coupon::generateCouponCode();
        $coupon->expiration_date = $payload['expiration_date'] ?? null;
        $coupon->number_of_uses = $payload['number_of_uses'] ?? 1;
        $coupon->reward_duration_in_months = $payload['reward_duration_in_months'] ?? null;
        return $coupon;
    }

    public static function generateCouponCode()
    {
        $isCodeUnique = false;
        $codeLength = 20;
        $availableCharacters = '123456789ABCDEFGHJKLMNOPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < $codeLength; $i++) {
                $code .= $availableCharacters[random_int(0, strlen($availableCharacters) - 1)];
            }
            $isCodeUnique = Coupon::checkUniqueCode($code);
        } while (!$isCodeUnique);
        return $code;
    }

    public static function checkUniqueCode($code)
    {
        $existingCoupon = Coupon::where('code', '=', $code)->first();
        return !$existingCoupon;
    }
}
