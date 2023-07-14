<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\SpendingAccountOverviewResource;
use App\Http\Resources\V1\SavingsAccessCCOverviewResource;
use App\Http\Resources\V1\IncomeAccountOverviewResource;
use App\Http\Resources\V1\CCPayoffAccountOverviewResource;
use App\Http\Resources\V1\AllocationAccountResource;
use App\Http\Resources\V1\ScheduleItemResource;
use App\Http\Resources\V1\TransactionResource;
use App\Http\Resources\V1\ParentTransactionResource;
use App\Jobs\FetchFinicityAccountTransactions;
use App\Models\DownloadRequest;
use App\Models\TransactionPlacement;
use \Carbon\Carbon;

class BankAccountsController extends Controller
{
    public function getSpendingAccountOverview()
    {
        $spendingAccount = Auth::user()->current_account->spendingAccount;
        if ($spendingAccount instanceof BankAccount) {
            $spendingAccount->loadOverviewAttributes();
        }
        return new SpendingAccountOverviewResource($spendingAccount);
    }

    public function getSavingsAccessCreditCardOverview()
    {
        $savingsAccessCreditCard = Auth::user()->current_account->savingsAccessCreditCard;
        if ($savingsAccessCreditCard instanceof BankAccount) {
            $savingsAccessCreditCard->loadOverviewAttributes();
        }
        return new SavingsAccessCCOverviewResource($savingsAccessCreditCard);
    }

    public function getCreditCardOverviews()
    {
        $creditCards = Auth::user()->current_account->creditCardAccounts;
        foreach ($creditCards as $creditCard) {
            $creditCard->loadOverviewAttributes();
        }
        return SavingsAccessCCOverviewResource::collection($creditCards);
    }

    public function getIncomeAccountOverview()
    {
        $incomeAccount = Auth::user()->current_account->incomeAccount()->with(
            'unclearedAllocationsOut',
            'institutionAccount'
        )->first();
        if ($incomeAccount instanceof BankAccount) {
            $incomeAccount->loadOverviewAttributes();
        }
        return new IncomeAccountOverviewResource($incomeAccount);
    }

    public function getCCPayoffAccountOverview()
    {
        $payoffAccount = Auth::user()->current_account->payoffAccount()->with(
            'account',
            'account.assignableAccounts',
            'account.assignableAccounts.assignments',
            'unclearedAllocations',
            'untransferredAssignments',
            'untransferredAssignments.transaction',
            'untransferredAssignments.allocations',
            'institutionAccount'
        )->first();
        if ($payoffAccount instanceof BankAccount) {
            $payoffAccount->loadOverviewAttributes();
        }
        return new CCPayoffAccountOverviewResource($payoffAccount);
    }

    public function getAllocationAccounts()
    {
        $allocationAccounts = Auth::user()->current_account->allocationAccounts()->with(
            'unclearedAllocations',
            'unclearedAllocationsOut',
            'untransferredAssignments',
            'untransferredAssignments.transaction',
            'untransferredAssignments.allocations',
            'institutionAccount'
        )->get();

        foreach ($allocationAccounts as $allocationAccount) {
            $allocationAccount->setAppends([
                'is_required',
                'balance_available',
                'assignment_balance_adjustment',
                'allocation_balance_adjustment'
            ]);
        }
        return AllocationAccountResource::collection($allocationAccounts);
    }

    public static function getScheduleItems($bankAccountId, $scheduleItemId = false)
    {
        $bankAccount = Auth::user()->current_account->bankAccounts()->with('schedule_items')->findOrFail($bankAccountId);
        $scheduleItems = $bankAccount->schedule_items;

        if ($scheduleItemId) {
            $scheduleItem = $scheduleItems->where('id', $scheduleItemId)->first();
            if ($scheduleItem) {
                return new ScheduleItemResource($scheduleItem);
            } else {
                throw new HttpException(404, 'Schedule Item not found.');
            }
        }
        return ScheduleItemResource::collection($scheduleItems);
    }

    public static function destroy($bankAccountId)
    {
        $user = Auth::user();
        $bankAccount = $user->current_account->bankAccounts()->findOrFail($bankAccountId);
        $canDeleteAccount = !$bankAccount->institution_account || $user->current_account_user->hasPermissionTo('manage finicity-accounts');
        if(!$canDeleteAccount) {
            abort(403, 'You don\'t have permission to delete this bank account');
        }
        if ($bankAccount && $bankAccount->delete()) {
            return response()->json(null, 204);
        } else {
            throw new HttpException(500, 'Failed to delete Bank Account');
        }
    }

    public function storeTransaction(Request $request, $bankAccountId, $transactionId)
    {
        Auth::user()->current_account->bankAccounts()->findOrFail($bankAccountId);
        $payload = $request->all();
        $this->checkBankAccountId($request, $bankAccountId);
        $this->checkTransactionId($request, $transactionId);
        $transaction = Transaction::mergeOrCreate($payload);
        if ($transaction->save()) {
            return new TransactionResource($transaction);
        } else {
            throw new HttpException(500, 'Failed to store Transaction');
        }
    }

    public function removeTransaction(Request $request, $bankAccountId, $transactionId)
    {
        $bankAccount = Auth::user()->current_account->bankAccounts()->findOrFail($bankAccountId);
        $transaction = $bankAccount->transactions()->findOrFail($transactionId);
        try {
            if ($transaction->remote_transaction_id) {
                $assignedSplitTransactions = $transaction->splitTransactions()->whereHas('assignment')->get();
                if (count($assignedSplitTransactions)) {
                    throw new HttpException(400, "Cannot delete transaction if split transactions have already been assigned.");
                }
                $transaction->is_assignable = false;
                $transaction->splitTransactions()->forceDelete();
                $transaction->save();
                $transaction->delete();
            } else {
                $transaction->forceDelete();
            }
            return response(null, 204);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);
            throw new HttpException(500, 'Failed to delete Transaction');
        }
    }

    public function getParentTransaction($bankAccountId, $transactionId)
    {
        $bankAccount = Auth::user()->current_account->bankAccounts()->whereHas('transactions', function ($query) use ($transactionId) {
            $query->where('id', $transactionId);
        })->first();
        if ($bankAccount) {
            $parentTransaction = Transaction::with([
                'splitTransactions',
                'splitTransactions.assignment',
                'splitTransactions.assignment.transaction',
                'splitTransactions.assignment.allocations'
                ])->findOrFail($transactionId);
            return new ParentTransactionResource($parentTransaction);
        } else {
            throw new HttpException(404, 'Unable to find parent transaction');
        }
    }

    protected function checkBankAccountId(Request $request, $bankAccountId)
    {
        $hasIncorrectBankAccountId = $request->input('bank_account_id') !== (integer)$bankAccountId;
        if ($hasIncorrectBankAccountId) {
            $statusCode = 409;
            $message = 'Incorrect bank account id';
            throw new HttpException($statusCode, $message);
        }
    }

    protected function checkTransactionId(Request $request, $transactionId)
    {
        $payload = $request->all();
        $hasTransactionId = isset($payload['id']) || $transactionId !== 'undefined';
        $hasIncorrectTransactionId = $hasTransactionId && array_get($payload, 'id') !== (integer)$transactionId;
        if ($hasIncorrectTransactionId) {
            $statusCode = 409;
            $message = 'Incorrect transaction id';
            throw new HttpException($statusCode, $message);
        }
    }

    public function requestTransactionDownload(Request $request, $bankAccountId)
    {
        $request->validate([
            'start_date' => 'date|required|before:today',
            'end_date' => 'date|required|after:start_date',
        ]);

        $bankAccount = Auth::user()->current_account->bankAccounts()->where('id', $bankAccountId)->first();
        if(!$bankAccount){
            abort(404, 'Cannot find the bank account');
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $transactions = $bankAccount->transactions()->withTrashed()->whereBetween('remote_transaction_date', [$startDate, $endDate])->get(['remote_transaction_date', 'remote_merchant', 'amount']);
        if($transactions->count() <= 0){
            abort(404, 'No transactions to download');
        }

        $json = [
            'bankAccountId' => $bankAccountId,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        $downloadEntry = DownloadRequest::addRequest($json, 'account-transaction-');
        return response()->json([
            'token' => $downloadEntry->token
        ]);
    }

    public function loadRecentBankTransactionsHistory($bankAccountId)
    {
        $bankAccount = BankAccount::findOrFail($bankAccountId);
        $isBillingAccount = $bankAccount->purpose === 'bills';
        $isIncomeAccount = $bankAccount->purpose === 'income';
        $isFirstSavingAccount = $bankAccount->purpose === 'savings' && $bankAccount->sub_account_order === 0;

        $parentAccount = $bankAccount->parent_bank_account_id ? BankAccount::find($bankAccount->parent_bank_account_id) : null;
        if(!$parentAccount){
            return response()->json([]);
        }

        // transaction history query
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $query = $parentAccount->transactions()
            ->where('remote_transaction_date', '>', $thirtyDaysAgo)
            ->where(function($query) use($bankAccount, $isBillingAccount, $isIncomeAccount, $isFirstSavingAccount){
                $query->whereHas('placement', function($query) use($bankAccount){
                    $query->where('bank_account_id', $bankAccount->id);
                })->orWhere(function($query) use($isBillingAccount, $isIncomeAccount, $isFirstSavingAccount){
                    $query
                        ->doesntHave('placement')
                        ->where(function($query) use($isBillingAccount, $isIncomeAccount, $isFirstSavingAccount){
                            if($isBillingAccount){
                                $query->where('amount', '>', 0);
                            } else if($isIncomeAccount){
                                $query->where('amount', '<', 0);
                            } else if(!$isFirstSavingAccount){
                                $query->whereNull('amount');
                            }
                        });
                });
            });


        $transactions = $query->get();

        return response()->json($transactions);
    }

    public function moveTransaction($bankAccountId, $transactionId, Request $request)
    {
        $bankAccount = BankAccount::findOrFail($bankAccountId);
        $transaction = Transaction::findOrFail($transactionId);
        $request->validate([
            'from' => 'required|numeric',
            'to' => 'required|numeric',
            'merchant' => 'required|string'
        ]);

        $fromAccountId = $request->from ?? 0;
        $toAccountId = $request->to ?? 0;
        $newMerchant = $request->merchant ?? '';

        $fromAccount = BankAccount::findOrFail($fromAccountId);
        $toAccount = BankAccount::findOrFail($toAccountId);

        if($fromAccount->parent_bank_account_id !== $bankAccount->id || $fromAccount->parent_bank_account_id !== $toAccount->parent_bank_account_id){
            abort(403, 'Cannot move transaction across different parent bank accounts');
        }

        $transactionPlacement = TransactionPlacement::where([
            ['bank_account_id', $fromAccountId],
            ['transaction_id', $transactionId]
        ])->first();

        if(!$transactionPlacement){
            TransactionPlacement::create([
                'bank_account_id' => $toAccountId,
                'transaction_id' => $transactionId
            ]);
        } else {
            $transactionPlacement->update([
                'bank_account_id' => $toAccountId
            ]);
        }

        $fromAccount->update([
            'balance_current' => $fromAccount->balance_current + $transaction->amount
        ]);
        $toAccount->update([
            'balance_current' => $toAccount->balance_current - $transaction->amount
        ]);

        $transaction->merchant = $newMerchant;
        $transaction->save();

        return 'OK';
    }
}
