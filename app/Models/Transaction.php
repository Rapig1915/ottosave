<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remote_transaction_date',
    ];

    public function bankAccount()
    {
        return $this->belongsTo('App\Models\BankAccount');
    }

    public function assignment()
    {
        return $this->hasOne('App\Models\Assignment');
    }

    public function placement()
    {
        return $this->hasOne('App\Models\TransactionPlacement');
    }

    public function allocation()
    {
        return $this->belongsTo('App\Models\BankAccount\Allocation');
    }

    public function parentTransaction()
    {
        return $this->belongsTo('App\Models\Transaction', 'parent_transaction_id');
    }

    public function splitTransactions()
    {
        return $this->hasMany('App\Models\Transaction', 'parent_transaction_id');
    }

    public function getMerchantAttribute()
    {
        $transaction = $this;
        $transactionHasMerchantOverride = $transaction->attributes['merchant'] ? decrypt($transaction->attributes['merchant']) !== '' : false;
        return $transactionHasMerchantOverride
            ? decrypt($transaction->attributes['merchant'])
            : $transaction->remote_merchant;
    }
    public function getRemoteMerchantAttribute()
    {
        $transaction = $this;
        return $transaction->attributes['remote_merchant'] ? decrypt($transaction->attributes['remote_merchant']) : '';
    }
    public function setMerchantAttribute($merchant)
    {
        $transaction = $this;
        $transaction->attributes['merchant'] = encrypt($merchant);
    }
    public function setRemoteMerchantAttribute($merchant)
    {
        $transaction = $this;
        $transaction->attributes['remote_merchant'] = encrypt($merchant);
    }

    public function getAmountAttribute()
    {
        $transaction = $this;
        return round($transaction->attributes['amount'], 2);
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $transaction = Transaction::findOrFail($payload['id']);
        } else {
            $transaction = new Transaction;
        }
        $transaction = Transaction::merge($transaction, $payload);
        return $transaction;
    }

    private static function merge(Transaction $transaction, $payload, $overwriteAllProperties = true)
    {
        $transaction->remote_account_id = $payload['remote_account_id'] ?? '';
        $transaction->remote_transaction_id = $payload['remote_transaction_id'] ?? '';
        $transaction->remote_category_id = $payload['remote_category_id'] ?? '';
        $transaction->remote_category = $payload['remote_category'] ?? '';
        $transaction->amount = $payload['amount'] ?? 0;
        $transaction->remote_merchant = $payload['remote_merchant'] ?? '';
        $transaction->remote_transaction_date = $payload['remote_transaction_date'] ?? null;
        $transaction->bank_account_id = $payload['bank_account_id'] ?? 0;
        $transaction->action_type = $payload['action_type'] ?? '';
        $transaction->parent_transaction_id = $payload['parent_transaction_id'] ?? null;
        if ($overwriteAllProperties || !$transaction->id) {
            $transaction->merchant = $payload['merchant'] ?? '';
            $transaction->is_assignable = $payload['is_assignable'] ?? true;
        }
        return $transaction;
    }

    public static function bulkUpdateOrCreateFromRemoteTransactions($remoteTransactions, BankAccount $bankAccount, $remoteSource = 'finicity')
    {
        if ($remoteSource === 'finicity') {
            return Transaction::bulkUpdateOrCreateFromFinicityTransactions($remoteTransactions, $bankAccount);
        }
    }

    private static function bulkUpdateOrCreateFromFinicityTransactions($finicityTransactions, BankAccount $bankAccount)
    {
        $bankAccountId = $bankAccount->id;
        $remoteTransactionIds = [];
        foreach ($finicityTransactions as $finicityTransaction) {
            $remoteTransactionIds[] = $finicityTransaction->id;
        }
        $existingTransactions = Transaction::where('bank_account_id', '=', $bankAccountId)->withTrashed()->whereIn('remote_transaction_id', $remoteTransactionIds)->get();
        $existingTransactionsKeyedByRemoteId = collect($existingTransactions)->keyBy('remote_transaction_id');

        $unsavedTransactions = [];
        foreach ($finicityTransactions as $finicityTransaction) {
            $shouldIgnoreTransaction = $finicityTransaction->status === 'pending';
            if ($shouldIgnoreTransaction) {
                continue;
            }
            $formattedTransaction = Transaction::formatFinicityTransaction($finicityTransaction);
            $transactionToMerge = $existingTransactionsKeyedByRemoteId[$formattedTransaction['remote_transaction_id']] ?? new Transaction;
            $unsavedTransactions[] = Transaction::merge($transactionToMerge, $formattedTransaction, false);
        }
        return $unsavedTransactions;
    }

    private static function formatFinicityTransaction($payload)
    {
        $formattedTransaction = [];
        $formattedTransaction['remote_account_id'] = $payload->accountId;
        $formattedTransaction['remote_transaction_id'] = $payload->id;
        $formattedTransaction['remote_category_id'] = null;
        $formattedTransaction['remote_category'] = $payload->categorization->category ?? null;
        $formattedTransaction['amount'] = -1 * $payload->amount; // finicity handles amounts such that positive amounts are deposits, negative are withdrawals
        $formattedTransaction['remote_merchant'] = ($payload->description ?? '') . ($payload->memo ?? '');
        $formattedTransaction['remote_transaction_date'] = Carbon::createFromTimestamp($payload->postedDate);
        $formattedTransaction['action_type'] = $payload->type ?? null;
        $formattedTransaction['is_assignable'] = true;

        return $formattedTransaction;
    }

    public function scopeDeletable($query)
    {
        return $query->where(function ($query) {
                $query->where('is_assignable', true)
                    ->orWhere(function ($query) {
                        $query->has('splitTransactions');
                    });
            })->whereNull('remote_transaction_id')
            ->doesntHave('parentTransaction')
            ->doesntHave('assignment');
    }
    public function scopeHideable($query)
    {
        return $query->where(function ($query) {
                $query->where('is_assignable', true)
                    ->orWhere(function ($query) {
                        $query->has('splitTransactions');
                    });
            })->whereNotNull('remote_transaction_id')
            ->whereDoesntHave('splitTransactions', function ($query) {
                $query->has('assignment');
            })
            ->doesntHave('parentTransaction')
            ->doesntHave('assignment');
    }
}
