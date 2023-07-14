<?php

namespace App\Http\Controllers\Api\V1\Authorized\BankAccount;

use App\Models\BankAccount\ScheduleItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\ScheduleItemResource;

class ScheduleItemController extends Controller
{
    public static function destroy($id)
    {
        $currentAccount = Auth::user()->current_account;
        $scheduleItem = ScheduleItem::with('bankAccount', 'bankAccount.account')->findOrFail($id);
        $userHasAccess = $scheduleItem && $scheduleItem->bankAccount->account->id === $currentAccount->id;

        if ($userHasAccess && $scheduleItem->delete()) {
            return response()->json(null, 204);
        } elseif (!$userHasAccess) {
            throw new HttpException(403, "You do not have access to this account");
        } else {
            throw new HttpException(500, "Failed to delete Schedule Item.");
        }

    }

    public static function storeScheduleItem(Request $request)
    {
        $payload = $request->all();
        Auth::user()->current_account->bankAccounts()->findOrFail($payload['bank_account_id']);
        $scheduleItem = ScheduleItem::mergeOrCreate($payload);
        if ($scheduleItem->save()) {
            return new ScheduleItemResource($scheduleItem);
        } else {
            throw new HttpException(500, "Failed to save Schedule Item.");
        }
    }

    public static function calculateMonthlyAmount(Request $request)
    {
        $payload = $request->all();
        $scheduleItem = ScheduleItem::mergeOrCreate($payload);
        return new ScheduleItemResource($scheduleItem);
    }
}
