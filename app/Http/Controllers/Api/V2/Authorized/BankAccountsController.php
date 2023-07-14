<?php

namespace App\Http\Controllers\Api\V2\Authorized;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\BankAccount;
use App\Http\Controllers\Controller;
use App\Http\Resources\V2\AssignableAccountResource;
use App\Http\Resources\V2\SavingsAccessCCResource;

class BankAccountsController extends Controller
{
    public function getAssignableAccounts()
    {
        $assignableAccounts = Auth::user()->current_account->assignableAccounts()->with(
            'unclearedAllocations',
            'unclearedAllocationsOut',
            'untransferredAssignments',
            'untransferredAssignments.transaction',
            'untransferredAssignments.allocations'
        )->get();

        foreach ($assignableAccounts as $assignableAccount) {
            $assignableAccount->setAppends([
                'is_required',
                'balance_available',
                'assignment_balance_adjustment',
                'allocation_balance_adjustment'
            ]);
        }
        return AssignableAccountResource::collection($assignableAccounts);
    }

    public function getSavingsAccessCC()
    {

        $savingsAccessCCAccount = Auth::user()->current_account->savingsAccessCreditCard;
        if ($savingsAccessCCAccount) {
            $savingsAccessCCAccount->loadMissing(
                'institutionAccount',
                'institutionAccount.institution'
            );
        }
        return new SavingsAccessCCResource($savingsAccessCCAccount);
    }
}
