<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AssignmentMiniResource;
use App\Models\Assignment;
use App\Models\Transaction;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\NewlyAssignedTransactionResource;
use App\Http\Resources\V1\TransactionResource;
use App\Http\Resources\V1\AssignmentResource;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    public function setAssignTransaction(Request $request)
    {
        bcscale(config('app.bcscale'));
        $this->validate($request, [
            'bank_account_id' => 'required|int',
            'transaction_id' => 'required_without:transaction_ids|int',
            'transaction_ids' => 'required_without:transaction_id|array'
        ]);

        $payload = $request->all();
        $bankAccount = Auth::user()->current_account->assignableAccounts()->with(
            'unclearedAllocations',
            'untransferredAssignments',
            'recentlyTransferredAssignments',
            'untransferredAssignments.transaction',
            'recentlyTransferredAssignments.transaction'
        )->find($payload['bank_account_id']);
        if (!$bankAccount) {
            $payoffAccount = Auth::user()->current_account->payoffAccount;
            $bankAccount = $payoffAccount->id === $payload['bank_account_id'] ? $payoffAccount :  null;
        }

        if (!$bankAccount) {
            throw new HttpException(403, "You do not have access to this account");
        }

        $bankAccount->loadMissing(
            'unclearedAllocations',
            'untransferredAssignments',
            'recentlyTransferredAssignments',
            'untransferredAssignments.transaction',
            'recentlyTransferredAssignments.transaction'
        );

        $isAssignedToUnlinkedPayoffAccount = $bankAccount->slug === 'cc_payoff' && !$bankAccount->institution_account_id;
        $isPayoffSubAccount = $bankAccount->slug === 'cc_payoff' && $bankAccount->parent_bank_account_id;
        $transactionIds = ($payload['transaction_id'] ?? null) ? [$payload['transaction_id']] : $payload['transaction_ids'];

        $transactions = Transaction::whereIn('id', $transactionIds)->get();
        $assignmentTotal = 0;
        foreach ($transactions as $transaction) {
            $assignmentTotal = bcadd($assignmentTotal, $transaction->amount);
        }
        if ($isAssignedToUnlinkedPayoffAccount) {
            $newAccountBalance = bcadd($bankAccount->balance_current, $assignmentTotal);
        } else if ($bankAccount->slug === 'cc_payoff') {
            $newAccountBalance = $bankAccount->balance_available;
        } else {
            $newAccountBalance = bcsub($bankAccount->balance_available, $assignmentTotal);
        }
        $balanceWillBeNegative = $newAccountBalance < 0;
        $willAssignmentBalanceAdjustmentBeNegative = bcadd($bankAccount->assignment_balance_adjustment, $assignmentTotal) < 0;

        if ($willAssignmentBalanceAdjustmentBeNegative && $bankAccount->slug !== 'cc_payoff') {
            abort(400, 'Assignment cancelled to prevent negative assignment total.');
        } elseif ($balanceWillBeNegative && !$isPayoffSubAccount) {
            $statusCode = 422;
            $message = 'Assignment cancelled to prevent negative account balance.';
            abort($statusCode, $message);
        }
        try {
            DB::beginTransaction();
            $assignments = [];
            foreach ($transactions as $transaction) {
                $assignmentDetails = [
                    'bank_account_id' => $payload['bank_account_id'],
                    'transaction_id' => $transaction->id
                ];
                $assignments[] = Assignment::mergeOrCreate($assignmentDetails);
            }

            $bankAccount->assignments()->saveMany($assignments);

            if ($isAssignedToUnlinkedPayoffAccount && !$isPayoffSubAccount) {
                $bankAccount->balance_current = $newAccountBalance;
                $bankAccount->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        if (isset($payload['transaction_id'])) {
            $assignment = Assignment::whereIn('transaction_id', $transactionIds)->with('transaction')->first();
            return new NewlyAssignedTransactionResource($assignment, $newAccountBalance);
        } else {
            $assignments = Assignment::whereIn('transaction_id', $transactionIds)->with('transaction')->get();
            $responsePayload = [];
            foreach ($assignments as $assignment) {
                $responsePayload[] = new NewlyAssignedTransactionResource($assignment, $newAccountBalance);
            }
            return response()->json($responsePayload);
        }
    }

    public function deleteAssignment($assignmentId)
    {
        $assignment = Assignment::with('bankAccount', 'bankAccount.parent_bank_account')->findOrFail($assignmentId);
        $userHasAccess = $assignment->bankAccount->account_id === (int)request()->header('current-account-id');
        $willAssignmentTotalBeNegative = bcsub($assignment->bankAccount->assignment_balance_adjustment, $assignment->transaction->amount) < 0;
        if ($willAssignmentTotalBeNegative && $assignment->bankAccount->slug !== 'cc_payoff') {
            throw new HttpException(400, 'Assignment removal cancelled to prevent negative assignment total.');
        } elseif (!$userHasAccess) {
            throw new HttpException(403, "You do not have access to this account");
        }
        $assignment->delete();

        return response()->json(null, 204);
    }

    public function deleteUnassignedTransactions()
    {
        $creditCardAccounts = Auth::user()->current_account->creditCardAccounts()->get();
        $creditCardAccountIds = $creditCardAccounts->pluck('id')->all();
        $deletableTransactions = Transaction::deletable()->whereIn('bank_account_id', $creditCardAccountIds)->get();
        $deletableTransactionIds = $deletableTransactions->pluck('id')->all();
        Transaction::destroy($deletableTransactionIds);
        $hidableTransasctions = Transaction::hideable()->whereIn('bank_account_id', $creditCardAccountIds)->get();
        $hidableTransasctionIds = $hidableTransasctions->pluck('id')->all();
        Transaction::whereIn('id', $hidableTransasctionIds)->update(['is_assignable' => false]);
        Transaction::whereIn('parent_transaction_id', $hidableTransasctionIds)->delete();
        return response()->json(null, 204);
    }

    public function indexAssignmentsByTransactionDate(Request $request)
    {
        $this->validate($request, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        $startDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $endDate = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $miniMode = $request->input('mini', false);

        $assignments = Assignment::with('transaction')->whereHas('transaction', function ($query) use ($startDate, $endDate) {
            $query->whereBetween(DB::raw('DATE(remote_transaction_date)'), array($startDate, $endDate));
        })->get();

        if($miniMode)
            return AssignmentMiniResource::collection($assignments);
        return AssignmentResource::collection($assignments);
    }
}
