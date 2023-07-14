<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BankAccount\Allocation;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Defense extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'end_date',
        'spending_account_starting_balance',
        'balance_snapshots',
    ];

    protected $appends = [
        'is_current'
    ];

    protected $casts = [
        'allocated' => 'boolean'
    ];

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function allocation()
    {
        return $this->hasMany('App\Models\BankAccount\Allocation');
    }

    public function getIsCurrentAttribute()
    {
        $defense = $this;
        $isCurrent = Carbon::now() < $defense->end_date;
        return $isCurrent;
    }

    public function getBalanceSnapshotsAttribute($value)
    {
        if (is_string($value)) {
            $value = json_decode($value);
        }
        return $value;
    }

    public function setBalanceSnapshotsAttribute($value)
    {
        $defense = $this;
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        $defense->attributes['balance_snapshots'] = $value;
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $defense = Defense::findOrFail($payload['id']);
        } else {
            $defense = new Defense;
        }

        return $defense;
    }

    public static function createForAccount(Account $account, $payload = [])
    {
        $defenseInterval = $account->projected_defenses_per_month === 1 ? 30 : 15;
        $defense = $account->defenses()->create([
            'end_date' => Carbon::now()->startOfDay()->addDays($defenseInterval)->toDateTimeString(),
            'spending_account_starting_balance' => $account->spendingAccount ? $account->spendingAccount->balance_current : 0,
            'balance_snapshots' => $account->getBalanceSnapshots(),
        ]);
        return $defense;
    }
}
