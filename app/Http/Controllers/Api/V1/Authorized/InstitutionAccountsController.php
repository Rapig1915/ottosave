<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FinicityOauthDeleteException;
use App\Jobs\FetchFinicityAccountTransactions;
use App\Events\InstitutionAccountCountChanged;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Resources\V2\InstitutionAccountResource;
use Illuminate\Support\Facades\Validator;

class InstitutionAccountsController extends Controller
{
    public function destroy($institution_account_id){
        $currentAccount = Auth::user()->current_account;
        $institutionAccount = $currentAccount->institutionAccounts()->findOrFail($institution_account_id);
        try {
            DB::beginTransaction();

            $institutionAccount->delete();
            $wasLastAccountForInstitution = count($institutionAccount->institution->institutionAccount) === 0;
            if ($wasLastAccountForInstitution) {
                $institutionAccount->institution->delete();
            }

            DB::commit();
        } catch (ClientException $e) {
            DB::rollback();
            $responseBody = json_decode($e->getResponse()->getBody());
            $errorCode = $responseBody->code ?? null;
            $failedToDeleteOauthAccount = $errorCode === 4034;
            if ($failedToDeleteOauthAccount) {
                throw new FinicityOauthDeleteException("Access tokens must be managed at the institution's website.");
            } else {
                $errorMessage = $responseBody->message;
                abort(400, $errorMessage);
            }
        } catch (\Exception $e) {
            DB::rollback();
            report($e);
            abort(500, "Unable to remove institution account");
        }
        event(new InstitutionAccountCountChanged($currentAccount));
        return response()->json(null, 204);
    }

    public function downloadPastTransactions($institution_account_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);
        $validator->validate();
        $today = Carbon::now();
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        if ($today->lessThan($startDate)) {
            abort(400, 'Start date must be a date in the past.');
        } elseif ($today->lessThan($endDate)) {
            abort(400, 'End date must be a date in the past.');
        }
        $institutionAccount = Auth::user()->current_account->institutionAccounts()->findOrFail($institution_account_id);
        $institutionAccount->getOrCreatePendingFinicityRefresh();
        FetchFinicityAccountTransactions::dispatch($institutionAccount, $startDate, $endDate);
        return response()->json(null, 204);
    }

    public function updateInstitutionAccount($institution_account_id)
    {
        $currentAccount = Auth::user()->current_account;
        $institutionAccount = $currentAccount->institutionAccounts()->findOrFail($institution_account_id);
        $institutionAccount->merge(request()->all());
        $institutionAccount->save();
        return new InstitutionAccountResource($institutionAccount);
    }
}
