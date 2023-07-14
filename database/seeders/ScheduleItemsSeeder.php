<?php

namespace Database\Seeders;

use App\Models\BankAccount\ScheduleItem;
use App\Models\BankAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleItemsSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ScheduleItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $bankAccounts = BankAccount::get();

        $scheduleItemPayloads = [
            [
                'bank_account_id' => $bankAccounts[0]->id,
                'description' => 'Improvement Savings',
                'amount_total' => 1700,
                'type' => 'monthly',
            ],
            [
                'bank_account_id' => $bankAccounts[1]->id,
                'description' => 'Description 2',
                'amount_total' => 1600,
                'type' => 'quarterly',
            ],
            [
                'bank_account_id' => $bankAccounts[2]->id,
                'description' => 'Description 3',
                'amount_total' => 2400,
                'type' => 'yearly',
            ],
            [
                'bank_account_id' => $bankAccounts[2]->id,
                'description' => 'Description 4',
                'amount_total' => 3200,
                'type' => 'target_date',
                'date_end' => '2019-12-31',
            ],
        ];

        foreach($scheduleItemPayloads as $scheduleItemPayload){
            ScheduleItem::mergeOrCreate($scheduleItemPayload)->save();
        }
    }
}
