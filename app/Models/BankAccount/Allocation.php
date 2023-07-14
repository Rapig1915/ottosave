<?php

namespace App\Models\BankAccount;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Allocation extends Model
{
    use HasFactory;
    
    protected $casts = [
        'cleared' => 'boolean',
        'transferred' => 'boolean',
        'cleared_out' => 'boolean'
    ];

    public function bankAccount(){
        return $this->belongsTo('App\Models\BankAccount');
    }

    public function transferredFromBankAccount()
    {
        return $this->belongsTo('App\Models\BankAccount', 'transferred_from_id');
    }

    public function defense()
    {
        return $this->belongsTo('App\Models\Defense');
    }

    public function assignments()
    {
        return $this->belongsToMany('App\Models\Assignment');
    }

    public function parent_allocation()
    {
        return $this->belongsTo('App\Models\BankAccount\Allocation');
    }

    public function child_allocations()
    {
        return $this->hasMany('App\Models\BankAccount\Allocation');
    }

    public function transaction()
    {
        return $this->hasOne('App\Models\Transaction');
    }

    public function getAmountAttribute()
    {
        $allocation = $this;
        return round($allocation->attributes['amount'], 2);
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])){
            $allocation = Allocation::findOrFail($payload['id']);
        } else {
            $allocation = new Allocation;
        }

        $allocation->amount = isset($payload['amount']) ? preg_replace('/[^0-9.]/', '', $payload['amount']) : 0;
        $allocation->defense_id = $payload['defense_id'] ?? null;
        $allocation->bank_account_id = $payload['bank_account_id'];
        $allocation->transferred = $payload['transferred'] ?? false;
        $allocation->cleared_out = $payload['cleared_out'] ?? false;
        $allocation->transferred_from_id = $payload['transferred_from_id'] ?? null;
        $allocation->cleared = $payload['cleared'] ?? false;

        return $allocation;
    }
}
