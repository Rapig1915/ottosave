<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\BankAccount\ScheduleItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends Model
{
    use HasFactory;

    protected $appends = [
        'is_required'
    ];

    public $fillable = [
        'name',
        'account_id',
        'slug',
        'type',
        'color',
        'icon',
        'appears_in_account_list',
        'sub_account_order',
        'balance_current',
        'online_banking_url'
    ];

    // There are four types of accounts: income, checking, credit, and savings
    public static $defaultBankAccounts = [
        [
            'name' => 'Income Account',
            'slug' => 'income_deposit',
            'type' => 'income',
            'color' => 'gray',
            'icon' => 'square',
            'appears_in_account_list' => false,
        ], [
            'name' => 'Spending Account',
            'slug' => 'everyday_checking',
            'type' => 'checking',
            'color' => 'green',
            'icon' => 'square',
            'appears_in_account_list' => false,
        ], [
            'name' => 'Bills Account',
            'slug' => 'monthly_bills',
            'type' => 'checking',
            'color' => 'pink',
            'icon' => 'square',
            'appears_in_account_list' => false,
        ], [
            'name' => 'Credit Card Payoff Account',
            'slug' => 'cc_payoff',
            'type' => 'savings',
            'color' => 'gray-alt',
            'icon' => 'square',
            'appears_in_account_list' => false,
        ], [
            'name' => 'Credit Card',
            'slug' => 'savings_credit_card',
            'type' => 'credit',
            'color' => 'gold',
            'icon' => 'credit-card',
            'appears_in_account_list' => false,
        ]
    ];

    public static $requiredSlugs = [
        'income_deposit',
        'everyday_checking',
        'monthly_bills',
        'savings_credit_card',
        'cc_payoff'
    ];

    const CREDIT_CARD_COLORS = [
        'gold',
        'silver',
        'bronze',
    ];

    const SAVINGS_ACCOUNT_COLORS = [
        'violet',
        'orange',
        'cyan',
        'yellow',
        'purple',
    ];

    const PURPOSES = [
        'none',
        'income',
        'bills',
        'cc_payoff',
        'spending',
        'savings',
        'credit',
        'unassigned',
        'primary_savings',
        'primary_checking'
    ];

    protected static function boot()
    {
        parent::boot();
        bcscale(config('app.bcscale'));

        static::deleting(function ($bankAccount) {
            $isSavingsAccess = $bankAccount->slug === 'savings_credit_card';
            if ($isSavingsAccess) {
                BankAccount::whereNull('slug')->where('account_id', $bankAccount->account_id)->where('type', 'credit')->limit(1)->update(['slug' => 'savings_credit_card']);
            }
            $isBillsAccount = $bankAccount->slug === 'monthly_bills';
            if ($isBillsAccount) {
                ScheduleItem::where('bank_account_id', $bankAccount->id)->update([
                    'bank_account_slug' => $bankAccount->slug,
                    'bank_account_id' => null,
                    'account_id' => $bankAccount->account_id
                ]);
            }
            $removeSubAccounts = in_array($bankAccount->slug, ['primary_savings', 'primary_checking']);
            if ($removeSubAccounts) {
                foreach ($bankAccount->sub_accounts as $subAccount) {
                    $subAccount->delete();
                }
            }
        });
        static::deleted(function ($bankAccount) {
            $wasRequiredBankAccount = $bankAccount->slug && in_array($bankAccount->slug, BankAccount::$requiredSlugs);
            if ($wasRequiredBankAccount) {
                $bankAccount->account->createRequiredBankAccounts();
            }
            $parentAccount = $bankAccount->parent_bank_account_id ? BankAccount::find($bankAccount->parent_bank_account_id) : null;
            if ($parentAccount) {
                $parentAccount->refreshSubAccountBalances();
                $parentAccount->resetSubAccountOrder();
            }
        });
        static::saving(function ($bankAccount) {
            $oldSlug = $bankAccount->getOriginal('slug');
            $replaceCurrentDefault = $bankAccount->isDirty('slug')
                && $bankAccount->slug
                && in_array($bankAccount->slug, BankAccount::$requiredSlugs);
            $removeSubAccounts = $bankAccount->isDirty('slug')
                && in_array($oldSlug, ['primary_savings', 'primary_checking']);
            if ($replaceCurrentDefault) {
                $isBillsAccount = $bankAccount->slug === 'monthly_bills';
                $wasBillsAccount = $oldSlug === 'monthly_bills';
                $oldBillsAccount = $wasBillsAccount ? $bankAccount : BankAccount::where('slug', $bankAccount->slug)
                    ->where('account_id', $bankAccount->account_id)
                    ->where('id', '!=', $bankAccount->id)
                    ->first();
                if (($isBillsAccount || $wasBillsAccount) && $oldBillsAccount) {
                    ScheduleItem::where('bank_account_id', $oldBillsAccount->id)->update([
                        'bank_account_slug' => 'monthly_bills',
                        'bank_account_id' => null,
                        'account_id' => $oldBillsAccount->account_id
                    ]);
                }
                BankAccount::where('slug', $bankAccount->slug)
                    ->where('account_id', $bankAccount->account_id)
                    ->where('appears_in_account_list', false)
                    ->delete();
            }
            if ($removeSubAccounts) {
                foreach ($bankAccount->sub_accounts as $subAccount) {
                    $subAccount->delete();
                }
            }
        });

        static::saved(function ($bankAccount) {
            $isBillsAccount = $bankAccount->slug === 'monthly_bills';
            if ($isBillsAccount) {
                ScheduleItem::whereNull('bank_account_id')
                    ->where('account_id', $bankAccount->account_id)
                    ->where('bank_account_slug', 'monthly_bills')
                    ->update(['bank_account_slug' => $bankAccount->slug, 'bank_account_id' => $bankAccount->id]);
            }
        });
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        switch (array_get((array) $attributes, 'slug')) {
            case 'income_deposit':
                $model = new \App\Models\BankAccount\IncomeBankAccount;
                break;
            case 'everyday_checking':
                $model = new \App\Models\BankAccount\SpendingAccount;
                break;
            case 'savings_credit_card':
                $model = new \App\Models\BankAccount\SavingsAccessCreditCard;
                break;
            case 'cc_payoff':
                $model = new \App\Models\BankAccount\PayoffAccount;
                break;
            case 'primary_savings':
            case 'primary_checking':
            case 'unassigned':
                $model = new \App\Models\BankAccount;
                break;
            default:
                $model = array_get((array) $attributes, 'type') === 'credit' ? new \App\Models\BankAccount\CreditCardAccount : new \App\Models\BankAccount\AssignableBankAccount;
        }
        $model->exists = true;
        $model->setRawAttributes((array) $attributes, true);
        $model->setConnection($connection ?: $this->getConnectionName());
        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function institutionAccount()
    {
        return $this->belongsTo('App\Models\InstitutionAccount');
    }

    public function allocations()
    {
        return $this->hasMany('App\Models\BankAccount\Allocation', 'bank_account_id');
    }

    public function unclearedAllocations()
    {
        return $this->hasMany('App\Models\BankAccount\Allocation', 'bank_account_id')->where('transferred', '=', 1)->where('cleared', '=', 0);
    }

    public function lastAllocation()
    {
        return $this->hasOne('App\Models\BankAccount\Allocation', 'bank_account_id')->where('amount','>',0)->latest();
    }

    public function schedule_items()
    {
        return $this->hasMany('App\Models\BankAccount\ScheduleItem', 'bank_account_id');
    }

    public function transactions()
    {
        return $this->hasMany('App\Models\Transaction', 'bank_account_id')
            ->orderBy('remote_transaction_date', 'desc');
    }

    public function transactionPlacements()
    {
        return $this->hasMany('App\Models\TransactionPlacement', 'bank_account_id');
    }

    public function allocationsOut()
    {
        return $this->hasMany('App\Models\BankAccount\Allocation', 'transferred_from_id');
    }

    public function unclearedAllocationsOut()
    {
        return $this->hasMany('App\Models\BankAccount\Allocation', 'transferred_from_id')->where('transferred', '=', 1)->where('cleared_out', '=', 0);
    }

    public function parent_bank_account()
    {
        return $this->belongsTo('App\Models\BankAccount', 'parent_bank_account_id', 'id');
    }

    public function sub_accounts()
    {
        return $this->hasMany('App\Models\BankAccount', 'parent_bank_account_id', 'id')->orderBy('sub_account_order', 'asc');
    }

    public function getIsRequiredAttribute()
    {
        if (!empty($this->attributes['slug'])) {
            return array_search($this->attributes['slug'], self::$requiredSlugs) !== false;
        } else {
            return false;
        }
    }

    public function getBalanceCurrentAttribute()
    {
        $bankAccount = $this;
        $bankAccountBalance = $bankAccount->attributes['balance_current'] ?? 0;
        if ($bankAccount->institutionAccount && !$bankAccount->is_balance_overridden) {
            $bankAccountBalance = $bankAccount->institutionAccount->balance_available;
        }
        return round($bankAccountBalance, 2);
    }
    public function getBalanceAvailableAttribute()
    {
        $bankAccount = $this;
        $accountBalance = $bankAccount->balance_current ?: 0;
        $accountBalance = bcadd(bcsub($accountBalance, $bankAccount->assignment_balance_adjustment), $bankAccount->allocation_balance_adjustment);
        return round($accountBalance, 2);
    }
    public function getBalanceLimitAttribute()
    {
        $bankAccount = $this;

        if($bankAccount->balance_limit_override > 0) {
            return $bankAccount->balance_limit_override;
        } else {
            return $bankAccount->institutionAccount->balance_limit ?? 0;
        }
    }
    public function getAllocationBalanceAdjustmentAttribute()
    {
        $bankAccount = $this;
        $balanceAdjustment = 0;
        if (in_array($bankAccount->slug, ['primary_savings', 'primary_checking'])) {
            $bankAccount->loadMissing('sub_accounts');
            foreach ($bankAccount->sub_accounts as $subAccount) {
                if ($subAccount->slug === 'cc_payoff') {
                    continue;
                }
                $balanceAdjustment = bcadd($balanceAdjustment, $subAccount->allocation_balance_adjustment);
            }
        }
        return $balanceAdjustment;
    }

    public function getAssignmentBalanceAdjustmentAttribute()
    {
        $bankAccount = $this;
        $balanceAdjustment = 0;
        if (in_array($bankAccount->slug, ['primary_savings', 'primary_checking'])) {
            $bankAccount->loadMissing('sub_accounts');
            foreach ($bankAccount->sub_accounts as $subAccount) {
                if ($subAccount->slug === 'cc_payoff') {
                    continue;
                }
                $balanceAdjustment = bcadd($balanceAdjustment, $subAccount->assignment_balance_adjustment);
            }
        }
        return $balanceAdjustment;
    }

    public function getIsLinkedAttribute()
    {
        return !!$this->institution_account_id;
    }

    public function getPurposeAttribute()
    {
        $bankAccount = $this;
        $purpose = 'none';
        if ($bankAccount->slug === 'income_deposit') {
            $purpose = 'income';
        } elseif ($bankAccount->slug === 'monthly_bills') {
            $purpose = 'bills';
        } elseif ($bankAccount->slug === 'cc_payoff') {
            $purpose = 'cc_payoff';
        } elseif ($bankAccount->slug === 'everyday_checking') {
            $purpose = 'spending';
        } elseif ($bankAccount->slug === 'primary_savings') {
            $purpose = 'primary_savings';
        } elseif ($bankAccount->slug === 'primary_checking') {
            $purpose = 'primary_checking';
        } elseif ($bankAccount->type === 'savings') {
            $purpose = 'savings';
        } elseif ($bankAccount->type === 'credit') {
            $purpose = 'credit';
        } elseif ($bankAccount->type === 'unassigned') {
            $purpose = 'unassigned';
        }
        return $purpose;
    }

    public function setPurposeAttribute($purpose)
    {
        $bankAccount = $this;
        if($purpose === 'income'){
            $bankAccount->slug = 'income_deposit';
            $bankAccount->type = 'income';
        } else if($purpose === 'bills'){
            $bankAccount->slug = 'monthly_bills';
            $bankAccount->type = 'checking';
        } else if($purpose === 'cc_payoff'){
            $bankAccount->slug = 'cc_payoff';
            $bankAccount->type = 'savings';
        } else if($purpose === 'spending'){
            $bankAccount->slug = 'everyday_checking';
            $bankAccount->type = 'checking';
        } else if($purpose === 'savings'){
            $bankAccount->slug = '';
            $bankAccount->type = 'savings';
        } else if($purpose === 'credit'){
            $bankAccount->slug = 'savings_credit_card';
            $bankAccount->type = 'credit';
        } else if($purpose === 'unassigned'){
            $bankAccount->slug = 'unassigned';
            $bankAccount->type = 'unassigned';
        } else if($purpose === 'primary_savings'){
            $bankAccount->slug = 'primary_savings';
            $bankAccount->type = 'savings';
        } else if($purpose === 'primary_checking'){
            $bankAccount->slug = 'primary_checking';
            $bankAccount->type = 'checking';
        } else {
            $bankAccount->slug = null;
            $bankAccount->type = null;
        }
        $purposesWithAssignments = [
            'savings',
            'bills',
            'cc_payoff',
            'spending'
        ];
        $hasAssignments = in_array("App\Traits\HasAssignments", class_uses_recursive($bankAccount));
        $shouldRemoveAssignments = $hasAssignments && !in_array($purpose, $purposesWithAssignments);
        if ($shouldRemoveAssignments) {
            $bankAccount->untransferredAssignments()->delete();
        }
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $bankAccount = BankAccount::findOrFail($payload['id']);
        } else {
            $bankAccount = new BankAccount();
        }

        $bankAccount->account_id = Auth::user()->current_account->id;
        $bankAccount->name = $payload['name'] ?? '';
        $bankAccount->type = array_key_exists('type', $payload) ? $payload['type'] : 'savings';
        $bankAccount->online_banking_url = $payload['online_banking_url'] ?? null;
        $bankAccount->is_balance_overridden = $payload['is_balance_overridden'] ?? false;
        $bankAccount->appears_in_account_list = $payload['appears_in_account_list'] ?? true;
        $bankAccount->balance_limit_override = $payload['balance_limit_override'] ?? 0;

        if (isset($payload['balance_current'])) {
            $bankAccount->balance_current = $payload['balance_current'];
        }

        if (array_key_exists('purpose', $payload)) {
            $bankAccount->purpose = $payload['purpose'];
        }

        if (array_key_exists('parent_bank_account_id', $payload)) {
            $bankAccount->parent_bank_account_id = $payload['parent_bank_account_id'] ?? null;
            $bankAccount->sub_account_order = $payload['sub_account_order'] ?? 1;
        }
        if ($bankAccount->parent_bank_account_id === null) {
            $bankAccount->sub_account_order = null;
        }

        $bankAccount->color = $payload['color'] ?? $bankAccount->getNextDefaultColor();
        $bankAccount->icon = $bankAccount->type === 'credit' ? 'credit-card' : 'square';

        return $bankAccount;
    }

    public function getNextDefaultColor()
    {
        $bankAccount = $this;
        if ($bankAccount->slug === 'income_deposit') {
            $color = 'gray';
        } elseif ($bankAccount->slug === 'everyday_checking') {
            $color = 'green';
        } elseif ($bankAccount->slug === 'monthly_bills') {
            $color = 'pink';
        } elseif ($bankAccount->slug === 'cc_payoff') {
            $color = 'gray-alt';
        } elseif ($bankAccount->type === 'credit') {
            $color = $bankAccount->getNextDefaultCCColor();
        } elseif ($bankAccount->type === 'savings')  {
            $color = $bankAccount->getNextDefaultSavingsColor();
        } else {
            $color = '';
        }
        return $color;
    }

    private function getNextDefaultSavingsColor()
    {
        $bankAccount = $this;
        $nonDefaultAccounts = $bankAccount->account->bankAccounts->whereNotIn('slug', self::$requiredSlugs)->where('type', '!=', 'credit');
        $colorChoices = BankAccount::SAVINGS_ACCOUNT_COLORS;
        foreach ($nonDefaultAccounts as $nonDefaultAccount) {
            $keyForColor = array_search($nonDefaultAccount->color, $colorChoices);
            if ($keyForColor !== false) {
                unset($colorChoices[$keyForColor]);
            }
            if (count($colorChoices) === 0) {
                $colorChoices = BankAccount::SAVINGS_ACCOUNT_COLORS;
            }
        }
        return reset($colorChoices);
    }

    private function getNextDefaultCCColor()
    {
        $bankAccount = $this;
        $colorChoices = BankAccount::CREDIT_CARD_COLORS;
        foreach ($bankAccount->account->creditCardAccounts as $creditCardAccount) {
            $keyForColor = array_search($creditCardAccount->color, $colorChoices);
            if ($keyForColor !== false) {
                unset($colorChoices[$keyForColor]);
            }
            if (count($colorChoices) === 0) {
                $colorChoices = array_merge(BankAccount::SAVINGS_ACCOUNT_COLORS, BankAccount::CREDIT_CARD_COLORS);
            }
        }
        return reset($colorChoices);
    }

    public function getSpentAfter(Carbon $date)
    {
        return $this->transactions()->where('remote_transaction_date', '>', $date)->sum('amount');
    }

    public function loadOverviewAttributes()
    {
        $bankAccount = $this;
        $bankAccount->setAppends([
            'is_required',
            'balance_available',
            'assignment_balance_adjustment',
            'allocation_balance_adjustment'
        ]);
    }

    public function getDateTwoBusinessDaysAgo()
    {
        $dayOfWeek = date('l');
        switch ($dayOfWeek) {
            case 'Monday':
            case 'Tuesday':
                $numberOfDaysIncludingTwoBusinessDays = 4;
                break;
            case 'Sunday':
                $numberOfDaysIncludingTwoBusinessDays = 3;
                break;
            default:
                $numberOfDaysIncludingTwoBusinessDays = 2;
                break;
        }
        $twoBusinessDaysAgo = Carbon::now()->subDays($numberOfDaysIncludingTwoBusinessDays)->startOfDay();
        return $twoBusinessDaysAgo;
    }

    public function clearAllocationsOut()
    {
        $bankAccount = $this;
        if ($bankAccount->parent_bank_account_id) {
            $allocationsToClear = $bankAccount->unclearedAllocationsOut()
                ->whereHas('parent_allocation', function ($query) {
                    $query->where('cleared_out', true);
                })->get();
            try {
                DB::beginTransaction();
                $allocationTotal = 0;
                foreach ($allocationsToClear as $unclearedAllocation) {
                    $allocationTotal = bcadd($allocationTotal, $unclearedAllocation->amount);
                }
                $bankAccount->balance_current = bcsub($bankAccount->balance_current, $allocationTotal);
                $bankAccount->save();
                $bankAccount->unclearedAllocationsOut()->whereIn('id', $allocationsToClear->pluck('id')->all())->update(['cleared_out' => 1]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } else {
            try {
                DB::beginTransaction();
                $allocationTotal = 0;
                foreach ($bankAccount->unclearedAllocationsOut as $unclearedAllocation) {
                    $allocationTotal = bcadd($allocationTotal, $unclearedAllocation->amount);
                }
                $bankAccount->balance_current = bcsub($bankAccount->balance_current, $allocationTotal);
                if ($bankAccount->is_linked) {
                    $bankAccount->is_balance_overridden = true;
                }
                $bankAccount->save();
                $bankAccount->unclearedAllocationsOut()->update(['cleared_out' => 1]);
                foreach ($bankAccount->sub_accounts as $subAccount) {
                    $subAccount->clearAllocationsOut();
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        }
    }

    public function clearAllocationsIn()
    {
        $bankAccount = $this;
        if ($bankAccount->parent_bank_account_id) {
            $allocationsToClear = $bankAccount->unclearedAllocations()
                ->whereHas('parent_allocation', function ($query) {
                    $query->where('cleared', true);
                })->get();
            try {
                DB::beginTransaction();
                $allocationTotal = 0;
                foreach ($allocationsToClear as $unclearedAllocation) {
                    $allocationTotal = bcadd($allocationTotal, $unclearedAllocation->amount);
                }
                $bankAccount->balance_current = bcadd($bankAccount->balance_current, $allocationTotal);
                $bankAccount->save();
                $bankAccount->unclearedAllocations()->whereIn('id', $allocationsToClear->pluck('id')->all())->update(['cleared' => 1]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } else {
            try {
                DB::beginTransaction();
                $allocationTotal = 0;
                foreach ($bankAccount->unclearedAllocations as $unclearedAllocation) {
                    $allocationTotal = bcadd($allocationTotal, $unclearedAllocation->amount);
                }
                $bankAccount->balance_current = bcadd($allocationTotal, $bankAccount->balance_current);
                if ($bankAccount->is_linked) {
                    $bankAccount->is_balance_overridden = true;
                }
                $bankAccount->save();
                $bankAccount->unclearedAllocations()->update(['cleared' => 1]);
                foreach ($bankAccount->sub_accounts as $subAccount) {
                    $subAccount->clearAllocations();
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        }
    }

    public function clearAllocations()
    {
        $bankAccount = $this;
        $bankAccount->clearAllocationsOut();
        $bankAccount->clearAllocationsIn();
    }

    public function manuallyClearAllocations()
    {
        $bankAccount = $this;
        $bankAccount->unclearedAllocations()->update(['cleared' => true]);
        $bankAccount->unclearedAllocationsOut()->update(['cleared_out' => true]);
    }

    public function initializeForDemo()
    {
        // base method, actual implementation found in child models
    }

    public function refreshSubAccountBalances()
    {
        $bankAccount = $this;
        $bankAccount->refresh();
        $bankAccount->load(
            'sub_accounts',
            'institutionAccount'
        );
        $subAccounts = collect($bankAccount->sub_accounts)->sortBy('sub_account_order');

        if ($subAccounts->count() > 0) {
            $targetSubAccount = null;
            $remainingParentBalance = $bankAccount->balance_current;
            
            foreach ($subAccounts as $subAccount) {
                $isTargetSubAccount = ($bankAccount->purpose === 'primary_checking' && $subAccount->slug === 'monthly_bills') || ($bankAccount->purpose === 'primary_savings' && $subAccount->sub_account_order === 0);
                if($isTargetSubAccount) {
                    $targetSubAccount = $subAccount;
                } else {
                    $remainingParentBalance = bcsub($remainingParentBalance, $subAccount->balance_current);
                }
            }

            if($targetSubAccount) {
                $targetSubAccount->balance_current = $remainingParentBalance;
                $targetSubAccount->save();
            }
        }
    }

    public function resetSubAccountOrder()
    {
        $bankAccount = $this;
        $bankAccount->loadMissing('sub_accounts');
        $subAccounts = collect($bankAccount->sub_accounts)->sortBy('sub_account_order');
        foreach ($subAccounts as $index => $subAccount) {
            $subAccount->sub_account_order = $index;
            $subAccount->save();
        }
    }
}
