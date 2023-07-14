<?php

namespace App\Models\BankAccount;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleItem extends Model
{
    use HasFactory;
    
    protected $table = 'schedule_items';

    public function bankAccount()
    {
        return $this->belongsTo('App\Models\BankAccount');
    }

    public function getAmountTotalAttribute()
    {
        $scheduleItem = $this;
        return round($scheduleItem->attributes['amount_total'], 2);
    }

    public function getAmountMonthlyAttribute()
    {
        $scheduleItem = $this;
        return round($scheduleItem->attributes['amount_monthly'], 2);
    }

    public static function mergeOrCreate($payload)
    {
        if (isset($payload['id'])) {
            $scheduleItem = ScheduleItem::findOrFail($payload['id']);
        } else {
            $scheduleItem = new ScheduleItem;
        }

        $scheduleItem->bank_account_id = $payload['bank_account_id'] ?? 0;
        $scheduleItem->description = $payload['description'] ?? '';
        $scheduleItem->amount_total = $payload['amount_total'] ?? 0;
        $scheduleItem->type = $payload['type'] ?? '';
        $scheduleItem->approximate_due_date = $payload['approximate_due_date'] ?? null;
        $scheduleItem->date_end = isset($payload['date_end']) ? substr($payload['date_end'], 0, 10) : null;

        $scheduleItem->setAmountMonthly();

        return $scheduleItem;
    }

    public function setAmountMonthly()
    {
        $scheduleItem = $this;
        $amountTotal = $scheduleItem->amount_total ?? 0;
        $type = strtolower($scheduleItem->type);
        switch ($type) {
            case 'monthly':
                $noOfMonths = 1;
                break;
            case 'quarterly':
                $noOfMonths = 3;
                break;
            case 'yearly':
                $noOfMonths = 12;
                break;
            default:
                $dateStart = isset($scheduleItem->date_start) && strtotime($scheduleItem->date_start) ?
                    new \DateTime($date_start) :
                    new \DateTime();
                $dateEnd = isset($scheduleItem->date_end) && strtotime($scheduleItem->date_end) ?
                    new \DateTime($scheduleItem->date_end) :
                    new \DateTime();
                $noOfDays = $dateStart->diff($dateEnd)->format('%a');
                $noOfMonths = max(1, ceil($noOfDays / 30));
                break;
        }

        $scheduleItem->amount_monthly = round(($amountTotal / $noOfMonths), 2);
    }
}
