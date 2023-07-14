<?php

namespace App\Http\Controllers\Api\V2\Authorized;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;

class AssignmentController extends Controller
{
    public function getUnassignedTransactions(){
        $creditCardAccounts = Auth::user()->current_account->creditCardAccounts()->with('unassignedTransactions')->get();
        $unassignedTransactions = $creditCardAccounts->pluck('unassignedTransactions')->flatten(1);
        return TransactionResource::collection($unassignedTransactions);
    }
}
