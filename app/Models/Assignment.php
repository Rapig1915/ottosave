<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;
    
    protected $casts = [
        'transferred' => 'boolean'
    ];

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'bank_account_id');
    }

    public function transaction()
    {
        return $this->belongsTo('App\Models\Transaction');
    }

    public function allocations()
    {
        return $this->belongsToMany('App\Models\BankAccount\Allocation');
    }

    public function getAllocatedAmountAttribute()
    {
        $assignment = $this;
        return round($assignment->attributes['allocated_amount'], 2);
    }

    public static function mergeOrCreate($payload)
    {
        if(isset($payload['id'])) {
            $assignment = Assignment::findOrFail($payload['id']);
        } else {
            $assignment = new Assignment;
        }
        Assignment::merge($assignment, $payload);
        return  $assignment;
    }

    public static function bulkMerge($payload)
    {
        $assignmentIds = collect($payload)->pluck('id')->all();
        $assignments = Assignment::whereIn('id', $assignmentIds)->with('transaction')->get();
        $payloadKeyedById = collect($payload)->keyBy('id')->all();
        foreach ($assignments as $assignment) {
            if ($payloadKeyedById[$assignment->id]) {
                Assignment::merge($assignment, $payloadKeyedById[$assignment->id]);
            }
        }
        return $assignments;
    }

    private static function merge(Assignment $assignment, $payload)
    {
        $assignment->transaction_id = $payload['transaction_id'];
        $assignment->bank_account_id = $payload['bank_account_id'];
        $assignment->transferred = $payload['transferred'] ?? false;
        $assignment->allocated_amount = $payload['allocated_amount'] ?? 0;

        if (isset($payload['transferred']) && !$payload['transferred']) {
            $assignment->transferred = $assignment->allocated_amount >= $assignment->transaction->amount;
        }
    }
}
