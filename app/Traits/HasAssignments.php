<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasAssignments
{
    public function assignments()
    {
        return $this->hasMany('App\Models\Assignment', 'bank_account_id');
    }

    public function untransferredAssignments()
    {
        return $this->hasMany('App\Models\Assignment', 'bank_account_id')->where('transferred', '=', 0)->with('transaction');
    }

    public function recentlyTransferredAssignments()
    {
        $twoBusinessDaysAgo = $this->getDateTwoBusinessDaysAgo();
        return $this->hasMany('App\Models\Assignment', 'bank_account_id')->where('transferred', '=', 1)->where('updated_at', '>', $twoBusinessDaysAgo)->with('transaction');
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
}
