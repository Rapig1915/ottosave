<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\BankAccount\Allocation;
use App\Models\BankAccount;
use App\Models\ITunesReceipt;
use App\Models\Defense;
use \App\Services\External\BraintreeService;
use \App\Services\SubscriptionService;
use Illuminate\Support\Facades\Mail;
use \App\Mail\Notifications\ExpiredAccountNotification;
use \Carbon\Carbon;
use App\Events\AccountStatusUpdated;
use App\Events\AccountSubscriptionPlanUpdated;
use App\Exceptions\SubscriptionExpiredException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'expire_date',
        'braintree_customer_id',
        'subscription_type',
        'subscription_plan',
        'subscription_provider',
        'subscription_origin',
        'is_trial_used',
        'projected_defenses_per_month',
        'is_locked'
    ];

    protected $appends = [
        'most_recent_defense'
    ];
    protected $casts = [
        'is_trial_used' => 'boolean',
        'is_locked' => 'boolean'
    ];
    private $subscriptionPlans = [
        'basic',
        'plus'
    ];

    protected static function boot()
    {
        parent::boot();
        static::saved(function ($account) {
            $isNewAccount = $account->getOriginal('status') === null;
            if (!$isNewAccount) {
                if ($account->isDirty('status')) {
                    event(new AccountStatusUpdated($account, $account->getOriginal('status'), $account->attributes['status']));
                }
                if ($account->isDirty('subscription_plan')) {
                    event(new AccountSubscriptionPlanUpdated($account, $account->getOriginal('subscription_plan'), $account->attributes['subscription_plan']));
                }
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function accountUsers()
    {
        return $this->hasMany('App\Models\AccountUser');
    }

    public function institutions()
    {
        return $this->hasMany('App\Models\Institution');
    }

    public function institutionAccounts()
    {
        return $this->hasManyThrough('App\Models\InstitutionAccount', 'App\Models\Institution');
    }

    public function bankAccounts()
    {
        return $this->hasMany('App\Models\BankAccount');
    }

    public function checkingAccounts()
    {
        return $this->hasMany('App\Models\BankAccount\CheckingBankAccount');
    }

    public function incomeAccount()
    {
        return $this->hasOne('App\Models\BankAccount\IncomeBankAccount');
    }

    public function assignableAccounts()
    {
        return $this->hasMany('App\Models\BankAccount\AssignableBankAccount');
    }

    public function savingsAccessCreditCard()
    {
        return $this->hasOne('App\Models\BankAccount\SavingsAccessCreditCard')->oldest();
    }

    public function creditCardAccounts()
    {
        return $this->hasMany('App\Models\BankAccount\CreditCardAccount');
    }

    public function spendingAccount()
    {
        return $this->hasOne('App\Models\BankAccount\SpendingAccount');
    }

    public function payoffAccount()
    {
        return $this->hasOne('App\Models\BankAccount\PayoffAccount');
    }

    public function allocationAccounts()
    {
        return $this->hasMany('App\Models\BankAccount\AssignableBankAccount', 'account_id')->whereNotIn('type', ['income', 'credit'])->where(function ($query) {
            $nonAllocationAccountSlugs = ['cc_payoff'];
            $query->whereNotIn('slug', $nonAllocationAccountSlugs)->orWhereNull('slug');
        });
    }

    public function requiredBankAccounts()
    {
        return $this->hasMany('App\Models\BankAccount')->whereIn('slug', BankAccount::$requiredSlugs);
    }

    public function defenses()
    {
        return $this->hasMany('App\Models\Defense');
    }

    public function paymentMethods()
    {
        return $this->hasMany('App\Models\PaymentMethod', 'braintree_customer_id', 'braintree_customer_id');
    }

    public function braintree_transactions()
    {
        return $this->hasMany('App\Models\BraintreeTransaction');
    }

    public function iTunesReceipts()
    {
        return $this->hasMany('App\Models\ITunesReceipt');
    }
    public function finicity_customer()
    {
        return $this->hasOne('App\Models\FinicityCustomer');
    }
    public function tapfiliate_customer()
    {
        return $this->hasOne('App\Models\TapfiliateCustomer');
    }
    public function redeemed_coupons()
    {
        return $this->belongsToMany('App\Models\Coupon')->withPivot('used_at', 'remaining_months');
    }

    public function getLatestItunesSubscriptionReceiptAttribute()
    {
        $account = $this;
        return $account->iTunesReceipts()->whereNotNull('expires_date')->latest()->first();
    }

    public function getMostRecentDefenseAttribute()
    {
        $account = $this;
        return $account->defenses()->with('allocation')->latest()->first();
    }

    public function setStatusAttribute($status)
    {
        $account = $this;
        $allowedStatuses = [
            'active',
            'free_trial',
            'cancelled', // 'cancelled' status is deprecated, when grace period expires account becomes 'active' for 'basic' plan
            'expired',
            'grace',
            'trial_grace',
            'pending_renewal',
            'deactivated',
            'demo'
        ];
        $statusIsValid = in_array($status, $allowedStatuses);
        if ($statusIsValid) {
            $account->attributes['status'] = $status;
        } else {
            throw new HttpException(422, "Attempting to set account status to invalid value " . $status);
        }
    }

    public function setSubscriptionPlanAttribute($subscriptionPlan)
    {
        $account = $this;
        $planIsValid = in_array($subscriptionPlan, $account->subscriptionPlans);
        if ($planIsValid) {
            $account->attributes['subscription_plan'] = $subscriptionPlan;
        } else {
            throw new HttpException(422, "Attempting to set account subscription plan to invalid value: " . $subscriptionPlan);
        }
    }

    public function setSubscriptionProviderAttribute($subscriptionProvider)
    {
        $account = $this;
        $allowedProviders = ['braintree', 'itunes'];
        $isProviderValid = in_array($subscriptionProvider, $allowedProviders);
        if ($isProviderValid) {
            $account->attributes['subscription_provider'] = $subscriptionProvider;
        } else {
            throw new HttpException(422, "Attempting to set account subscription provider to invalid value: " . $subscriptionProvider);
        }
    }

    public function setSubscriptionOriginAttribute($subscriptionOrigin)
    {
        $account = $this;
        $allowedOrigins = ['web', 'ios'];
        $isOriginValid = in_array($subscriptionOrigin, $allowedOrigins);
        if ($isOriginValid) {
            $account->attributes['subscription_origin'] = $subscriptionOrigin;
        } else {
            throw new HttpException(422, "Attempting to set account subscription origin to invalid value: " . $subscriptionOrigin);
        }
    }

    public function getCurrentDefense()
    {
        $account = $this;
        $mostRecentDefense = $account->most_recent_defense;

        if (!$mostRecentDefense || !$mostRecentDefense->is_current) {
            $currentDefense = Defense::createForAccount($account);
        } else {
            $currentDefense = $mostRecentDefense;
        }

        return $currentDefense;
    }

    public function getActiveDiscountCoupon()
    {
        $account = $this;
        return $account->redeemed_coupons()->where('reward_type', 'discount_percentage')->where('remaining_months', '>', 0)->orderBy('used_at', 'asc')->first();
    }

    public function consumeDiscountCoupon($couponId, $subscriptionType)
    {
        $account = $this;
        $discountCoupon = $account->redeemed_coupons()->where('reward_type', 'discount_percentage')->where('coupons.id', $couponId)->first();
        if ($discountCoupon) {
            $subscriptionInterval = $account->getSubscriptionTypes()[$subscriptionType]['interval'];
            $monthsConsumed = ($subscriptionInterval->y * 12) + $subscriptionInterval->m;
            $remainingDuration = $discountCoupon->pivot->remaining_months - $monthsConsumed;
            $account->redeemed_coupons()->updateExistingPivot($discountCoupon->id, ['remaining_months' => $remainingDuration]);
        }
    }

    public function getActiveDiscountPercent()
    {
        $account = $this;
        $activeDiscountCoupon = $account->getActiveDiscountCoupon();
        $activeDiscountPercent = $activeDiscountCoupon->amount ?? 0;
        return $activeDiscountPercent;
    }

    public function getSubscriptionChargeAmount()
    {
        $account = $this;
        $baseSubscriptionPrice = $account->getSubscriptionTypes()[$account->subscription_type]['price'];
        $activeDiscountPercent = $account->getActiveDiscountPercent();
        $discountAmount = $baseSubscriptionPrice * ($activeDiscountPercent / 100);
        $subscriptionCharge = bcsub($baseSubscriptionPrice, $discountAmount);
        return $subscriptionCharge;
    }

    public function renewSubscription()
    {
        $account = $this;
        if ($account->subscription_provider === 'braintree') {
            $account->renewSubscriptionThroughBraintree();
        } else if ($account->subscription_provider === 'itunes') {
            $account->renewSubscriptionThroughItunes();
            $account->status = 'active';
            $account->save();
        } else {
            throw new \Exception("Cannot renew subscription, unknown provider type: {$account->subscription_provider}");
        }
    }

    private function renewSubscriptionThroughBraintree()
    {
        $account = $this;
        if ($account->braintree_customer_id) {
            $subscriptionService = new SubscriptionService();
            $subscriptionService->renewSubscriptionThroughBraintree($account);
        } else if ($account->status === 'free_trial') {
            $account->status = 'trial_grace';
            $account->expire_date = Carbon::now()->addDays(15);
            $account->save();
        } else {
            throw new SubscriptionExpiredException("Subscription Expired");
        }
    }

    private function renewSubscriptionThroughItunes()
    {
        $account = $this;
        $latestReceipt = $account->latest_itunes_subscription_receipt;
        $verifiedReceipt = ITunesReceipt::verifySubscriptionReceipt($latestReceipt->encoded_receipt, $account->id);
        $account->updateSubscriptionFromITunesReceipt($verifiedReceipt);
    }

    public function updateSubscriptionFromITunesReceipt(ITunesReceipt $verifiedReceipt)
    {
        $account = $this;
        $account->expire_date = $verifiedReceipt->expires_date;
        $account->subscription_provider = 'itunes';
        $account->subscription_plan = 'plus';
        $account->subscription_type = $verifiedReceipt->product_id;
        $today = Carbon::today()->toDateString();
        $subscriptionExpired = Carbon::parse($account->expire_date) < $today;
        $itunesNotAttemptingRenewal = $verifiedReceipt->is_in_billing_retry_period !== '1';
        if ($subscriptionExpired && $itunesNotAttemptingRenewal) {
            throw new SubscriptionExpiredException("Subscription Expired");
        } else {
            $account->status = $verifiedReceipt->is_trial_period ? 'free_trial' : 'active';
        }
    }

    public function cleanupSubscriptionItems()
    {
        $account = $this;
        if ($account->finicity_customer) {
            $account->finicity_customer->delete();
        }
        $account->status = 'active';
        $account->subscription_plan = 'basic';
        $account->save();

        // invalidate any active coupons
        DB::table('account_coupon')->where('account_id', $account->id)->update(['remaining_months' => 0]);
    }

    public function incrementExpirationDate($subscriptionType)
    {
        $account = $this;
        $subscriptionInterval = $account->getSubscriptionTypes()[$subscriptionType]['interval'];
        $currentExpirationDate = new Carbon($account->expire_date);
        $today = Carbon::now();
        if ($currentExpirationDate->greaterThan($today)) {
            // handle cases where expiration has incremented since renewal process began such as coupon redemption
            $newExpirationDate = $currentExpirationDate->add($subscriptionInterval);
        } else {
            $newExpirationDate = $today->add($subscriptionInterval);
        }
        $account->expire_date = $newExpirationDate;
    }

    public function createRequiredBankAccounts()
    {
        $account = $this;
        $account->loadMissing('bankAccounts');
        $bankAccounts = collect($account->bankAccounts)->keyBy('slug');
        foreach (BankAccount::$defaultBankAccounts as $defaultBankAccount) {
            $requiredSlug = $defaultBankAccount['slug'];
            if (empty($bankAccounts[$requiredSlug])) {
                $defaultBankAccount['account_id'] = $account->id;
                $bankAccount = new BankAccount($defaultBankAccount);
                $bankAccount->save();
            }
        }
    }

    public function getSubscriptionTypes()
    {
        $account = $this;
        $subscriptionTypes = [
            'yearly' => [
                'price' => 44.99,
                'slug' => 'yearly',
                'name' => 'Yearly',
                'interval' => new \DateInterval('P1Y'),
                'cleared_for_sale' => false
            ],
            'yearly_55_99' => [
                'price' => 55.99,
                'slug' => 'yearly_55_99',
                'name' => 'Yearly',
                'interval' => new \DateInterval('P1Y'),
                'cleared_for_sale' => false
            ],
            'yearly_65_89' => [
                'price' => 65.89,
                'slug' => 'yearly_65_89',
                'name' => 'Yearly',
                'interval' => new \DateInterval('P1Y'),
                'cleared_for_sale' => false
            ],
            'yearly_87_89' => [
                'price' => 87.89,
                'slug' => 'yearly_87_89',
                'name' => 'Yearly',
                'interval' => new \DateInterval('P1Y'),
                'cleared_for_sale' => true
            ],
            'monthly' => [
                'price' => 3.99,
                'slug' => 'monthly',
                'name' => 'Monthly',
                'interval' => new \DateInterval('P1M'),
                'cleared_for_sale' => false
            ],
            'monthly_4_99' => [
                'price' => 4.99,
                'slug' => 'monthly_4_99',
                'name' => 'Monthly',
                'interval' => new \DateInterval('P1M'),
                'cleared_for_sale' => false
            ],
            'monthly_5_99' => [
                'price' => 5.99,
                'slug' => 'monthly_5_99',
                'name' => 'Monthly',
                'interval' => new \DateInterval('P1M'),
                'cleared_for_sale' => false
            ],
            'monthly_7_99' => [
                'price' => 7.99,
                'slug' => 'monthly_7_99',
                'name' => 'Monthly',
                'interval' => new \DateInterval('P1M'),
                'cleared_for_sale' => true
            ],
        ];
        return $subscriptionTypes;
    }

    public function setDefaultPaymentMethod($nonce)
    {
        $account = $this;
        $braintreeService = new BraintreeService();
        if ($account->braintree_customer_id) {
            $paymentMethodPayload = $braintreeService->createPaymentMethod($account->braintree_customer_id, $nonce);
            $paymentMethod = $account->paymentMethods()->where('token', '=', $paymentMethodPayload->token)->first();
            if (!$paymentMethod) {
                $account->paymentMethods()->update(['is_default' => 0]);
                $paymentMethod = PaymentMethod::create([
                    'token' => $paymentMethodPayload->token,
                    'is_default' => $paymentMethodPayload->default,
                    'braintree_customer_id' => $account->braintree_customer_id
                ]);
            } else {
                $paymentMethod->is_default = true;
                $paymentMethod->save();
            }
        } else {
            $braintreeCustomer = $braintreeService->addCustomerFromPaymentNonce($nonce);
            $paymentMethod = PaymentMethod::create([
                'token' => $braintreeCustomer->paymentMethods[0]->token,
                'is_default' => $braintreeCustomer->paymentMethods[0]->default,
                'braintree_customer_id' => $braintreeCustomer->id
            ]);
            $account->braintree_customer_id = $braintreeCustomer->id;
            $account->save();
        }
    }

    public function getIosSubscriptionProducts()
    {
        $account = $this;
        $ios_subscription_products = [
            [
                'product_id' => 'com.defendyourmoney.subscription.plus.monthly.1',
                'billing_interval' => 'month',
                'free_trial_period' => '',
            ],
            [
                'product_id' => 'com.defendyourmoney.subscription.plus.yearly.1',
                'billing_interval' => 'year',
                'free_trial_period' => '',
            ]
        ];
        return $ios_subscription_products;
    }

    public function sendExpirationNotificationToAllUsers()
    {
        $account = $this;
        $account->loadMissing('accountUsers', 'accountUsers.user');
        foreach ($account->accountUsers as $accountUser) {
            if ($accountUser->user->email_verified) {
                Mail::to($accountUser->user)->queue(new ExpiredAccountNotification($accountUser->user));
            }
        }
    }

    public function deactivate()
    {
        $account = $this;
        $account->status = 'deactivated';
        $account->subscription_plan = 'basic';
        $account->save();
        $account->institutions()->delete();
        if ($account->finicity_customer) {
            $account->finicity_customer->delete();
        }
        $account->defenses()->delete();
        $account->bankAccounts()->delete();
    }

    public function reactivate()
    {
        $account = $this;
        $account->status = 'active';
        $account->subscription_plan = 'basic';
        $account->subscription_type = 'monthly';
        $account->createRequiredBankAccounts();
        $account->save();
    }

    public function getNextDefenseDate()
    {
        $account = $this;
        if ($account->projected_defenses_per_month === 1) {
            $potentialEndDates = [
                Carbon::now()->startOfMonth()->addMonth(),
                Carbon::now()->startOfMonth()->addMonths(2)
            ];
        } elseif ($account->projected_defenses_per_month === 2) {
            $potentialEndDates = [
                Carbon::now()->startOfMonth()->addDays(15),
                Carbon::now()->startOfMonth()->addMonth(),
                Carbon::now()->startOfMonth()->addMonth()->addDays(15)
            ];
        }

        $threeDaysFromToday = Carbon::now()->startOfDay()->addDays(3);
        $defenseEndDate = null;
        foreach ($potentialEndDates as $potentialEndDate) {
            if ($threeDaysFromToday < $potentialEndDate) {
                $defenseEndDate = $potentialEndDate;
                break;
            }
        }

        if (!$defenseEndDate) {
            abort(500, 'Failed to find defense end date.');
        }
        return $defenseEndDate;
    }

    public function getBalanceSnapshots()
    {
        $account = $this;
        $account->loadMissing('bankAccounts', 'bankAccounts.institutionAccount');
        return $account->bankAccounts->reduce(function ($carry, $bankAccount) {
            $carry->{$bankAccount->id} = $bankAccount->balance_current;
            return $carry;
        }, new \stdClass);
    }

    public function initializeForDemo()
    {
        $account = $this;
        $account->status = 'demo';
        $account->is_trial_used = false;
        $account->subscription_plan = 'basic';
        $account->projected_defenses_per_month = 2;
        try {
            DB::beginTransaction();
            $account->save();
            $account->createRequiredBankAccounts();
            $nonRequiredBankAccounts = [
                [
                    'type' => 'savings',
                    'color' => 'violet',
                    'name' => 'Vacation Account',
                    'icon' => 'square',
                    'appears_in_account_list' => true,
                    'sub_account_order' => 6
                ],
                [
                    'type' => 'savings',
                    'color' => 'orange',
                    'name' => 'Entertainment Account',
                    'icon' => 'square',
                    'appears_in_account_list' => true,
                    'sub_account_order' => 5
                ],
                [
                    'type' => 'savings',
                    'color' => 'cyan',
                    'name' => 'Misc Account',
                    'icon' => 'square',
                    'appears_in_account_list' => true,
                    'sub_account_order' => 4
                ],
                [
                    'type' => 'checking',
                    'color' => '',
                    'name' => '',
                    'icon' => 'square',
                    'slug' => 'primary_checking',
                    'appears_in_account_list' => true,
                    'balance_current' => 2100
                ]
            ];
            foreach ($nonRequiredBankAccounts as $bankAccountPayload) {
                $account->bankAccounts()->create($bankAccountPayload);
            }
            $account->refresh();
            foreach ($account->bankAccounts as $bankAccount) {
                $bankAccount->initializeForDemo();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function accountInvites()
    {
        return $this->hasMany('App\Models\AccountInvite');
    }

    public static function addInvitedUser($user, $inviteCode)
    {
        $accountInvite = AccountInvite::getByCodeAndEmail($inviteCode, $user->email);
        $user->accounts()->save($accountInvite->account);
        $accountUser = $user->accountUsers()->where('account_id', $accountInvite->account->id)->first();
        $roleNames = $accountInvite->roles->pluck('name')->all();
        $accountUser->assignRole($roleNames);
        $accountInvite->markAccepted();
    }

    public function deleteFinicityCustomer()
    {
        $account = $this;
        if($account->finicity_customer){
            $account->institutions()->where('type', '=', 'finicity')->delete();
            $account->finicity_customer->delete();
        }
    }

    public function lock($flag = true)
    {
        $account = $this;
        $account->is_locked = $flag;
        $account->save();
    }
}
