<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPlacement extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'bank_account_id', 'transaction_id'
    ];

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'bank_account_id');
    }

    public function transaction()
    {
        return $this->belongsTo('App\Models\Transaction');
    }
}
