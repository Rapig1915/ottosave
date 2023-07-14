<?php

namespace App\Http\Controllers\Api\V3\Authorized;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\BankAccount;
use App\Http\Controllers\Controller;
use App\Http\Resources\V3\LinkedBankAccountResource;
use App\Models\Account;

class BankAccountsController extends Controller
{
    public function loadWithLinkedInstitutionAccounts(Request $request)
    {
        $currentAccount = Auth::user()->current_account;
        $currentAccount->createRequiredBankAccounts();
        $bankAccountQuery = $currentAccount->bankAccounts()->with(
            'institutionAccount',
            'institutionAccount.institution',
            'sub_accounts'
        );
        if ($request->query('type')) {
            $bankAccountQuery->where('type', $request->query('type'));
        }

        $bankAccounts = $bankAccountQuery->get();
        return LinkedBankAccountResource::collection($bankAccounts);
    }

    public function loadWithLinkedInstitutionAccountsOf($account_id, Request $request)
    {
        $account = Account::findOrFail($account_id);
        $bankAccountQuery = $account->bankAccounts()->with(
            'institutionAccount',
            'institutionAccount.institution',
            'sub_accounts'
        );
        if ($request->query('type')) {
            $bankAccountQuery->where('type', $request->query('type'));
        }

        $bankAccounts = $bankAccountQuery->get();
        return LinkedBankAccountResource::collection($bankAccounts);
    }

    public function retrieve(Request $request, $bankAccountId)
    {
        $user = Auth::user();
        $bankAccountQuery = $user->current_account->bankAccounts()->with(
            'institutionAccount',
            'institutionAccount.institution',
            'sub_accounts'
        );
        if($bankAccountId){
            $bankAccount = $bankAccountQuery->findOrFail($bankAccountId);
        } elseif ($request->query('slug')) {
            $bankAccount = $bankAccountQuery->where('slug', $request->query('slug'))->oldest()->firstOrFail();
        } else {
            abort(400, 'Must query by bank account id or slug.');
        }
        return new LinkedBankAccountResource($bankAccount);
    }

    public function createOrUpdate(Request $request)
    {
        $payload = $request->all();
        $validationRules = ['name' => 'nullable|string', 'online_banking_url' => 'active_url|nullable'];
        $validationMessages = ['online_banking_url.active_url' => 'A bank website url, that starts with https:// is required.'];
        $request->validate($validationRules, $validationMessages);

        if (isset($payload['id'])) {
            Auth::user()->current_account->bankAccounts()->findOrFail($payload['id']);
        }

        $reattempts = 0;
        $maxAttempts = 3;
        $success = false;

        while (!$success && $reattempts < $maxAttempts) {
            try {
                DB::beginTransaction();
                $bankAccount = BankAccount::mergeOrCreate($payload);
                if ($bankAccount->is_balance_overridden) {
                    $bankAccount->manuallyClearAllocations();
                }
                $bankAccount->save();
                DB::commit();
                $success = true;
            } catch (\Exception $exception) {
                DB::rollback();
                $reattempts++;
                if ($reattempts >= $maxAttempts) {
                    throw $exception;
                }
            }
        }

        $bankAccount->refresh();
        $bankAccount->loadOverviewAttributes();
        return new LinkedBankAccountResource($bankAccount);
    }

    public function clearPendingTransfers(Request $request, $bankAccountId)
    {
        if ($bankAccountId) {
            $bankAccount = Auth::user()->current_account->bankAccounts()->findOrFail($bankAccountId);
            $bankAccount->manuallyClearAllocations();
            $bankAccount->refresh();
            return new LinkedBankAccountResource($bankAccount);
        } else {
            foreach (Auth::user()->current_account->bankAccounts as $bankAccount) {
                $bankAccount->manuallyClearAllocations();
            }
            return LinkedBankAccountResource::collection(Auth::user()->current_account->bankAccounts);
        }
    }
}
