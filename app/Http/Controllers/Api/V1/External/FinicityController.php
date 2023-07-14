<?php

namespace App\Http\Controllers\Api\V1\External;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\V1\FinicityConnectLinkResource;
use App\Http\Resources\V1\FinicityOauthInstitutionResource;
use App\Http\Resources\V1\FinicityRefreshResource as FinicityRefreshResourceV1;
use App\Services\External\FinicityService;
use App\Jobs\InstitutionAccountBalanceJob;
use App\Jobs\RefreshFinicityAccountsJob;
use App\Models\InstitutionAccount;
use App\Models\FinicityCustomer;
use App\Models\Transaction;
use App\Models\FinicityOauthInstitution;
use Illuminate\Support\Facades\Validator;

class FinicityController extends Controller
{
    public function getFinicityConnectLink(Request $request, $institution_id)
    {
        $finicityService = new FinicityService();
        $type = $request->query('type');
        $excludeRedirectLink = $request->query('exclude_redirect_link', false);
        if ($type === 'fix') {
            $institution = Auth::user()->current_account->institutions()->findOrFail($institution_id);
            $customerId = Auth::user()->current_account->finicity_customer->customer_id;
            $connectLink = $finicityService->getConnectFixLink($customerId, $institution->credentials->remote_secret, $excludeRedirectLink);
        } else {
            $connectLink = $finicityService->getConnectLink(Auth::user(), $excludeRedirectLink);
        }
        return new FinicityConnectLinkResource($connectLink);
    }

    public function connectRedirectHandler(Request $request)
    {
        return view('finicity.connect-complete');
    }

    public function refreshFinicityInstitution($institution_id)
    {
        $finicityCustomer = Auth::user()->current_account->finicity_customer;
        $institution = Auth::user()->current_account->institutions()->findOrFail($institution_id);
        if ($finicityCustomer) {
            $finicityService = new FinicityService();
            $finicityService->refreshCustomerAccounts($finicityCustomer->customer_id);
            InstitutionAccountBalanceJob::dispatchSync($institution);
            return response()->json(null, 204);
        } else {
            throw new HttpException(403, "Account not authorized for this request.");
        }
    }

    public function refreshFinicityAccounts()
    {
        $currentAccount = Auth::user()->current_account;
        if ($currentAccount->finicity_customer) {
            $finicityService = new FinicityService();
            $finicityService->refreshCustomerAccounts($currentAccount->finicity_customer->customer_id);
        }
        return response()->json(null, 204);
    }

    public function refreshFinicityInstitutionAsync($institution_id)
    {
        $finicityCustomer = Auth::user()->current_account->finicity_customer;
        $institution = Auth::user()->current_account->institutions()->find($institution_id);
        if ($finicityCustomer && $institution) {
            $institution->institutionAccount()->update(['api_status' => '']);
        } elseif ($institution_id !== 'all' || !$finicityCustomer) {
            throw new HttpException(403, "Account not authorized for this request.");
        }
        $finicityCustomer->getOrCreatePendingFinicityRefresh();
        RefreshFinicityAccountsJob::dispatchSync($finicityCustomer, $institution_id);
        return response()->json(null, 204);
    }

    public function getFinicityRefreshLogs()
    {
        $finicityRefreshes = [];
        $currentAccount = Auth::user()->current_account;
        $currentAccount->load([
            'finicity_customer',
            'finicity_customer.latest_finicity_refresh',
            'institutions',
            'institutions.latest_finicity_refresh',
            'institutionAccounts',
            'institutionAccounts.initial_aggregation',
            'institutionAccounts.latest_finicity_refresh'
        ]);
        $hasFinicityCustomerRefresh = $currentAccount->finicity_customer && $currentAccount->finicity_customer->latest_finicity_refresh;
        if ($hasFinicityCustomerRefresh) {
            $finicityRefreshes[] = $currentAccount->finicity_customer->latest_finicity_refresh;
        }
        foreach ($currentAccount->institutions as $institution) {
            if ($institution->latest_finicity_refresh) {
                $finicityRefreshes[] = $institution->latest_finicity_refresh;
            }
        }
        foreach ($currentAccount->institutionAccounts as $institutionAccount) {
            if ($institutionAccount->latest_finicity_refresh) {
                $finicityRefreshes[] = $institutionAccount->latest_finicity_refresh;
            }
            if ($institutionAccount->initial_aggregation) {
                $finicityRefreshes[] = $institutionAccount->initial_aggregation;
            }
        }
        return FinicityRefreshResourceV1::collection(collect($finicityRefreshes));
    }

    public function createOauthInstitution(Request $request)
    {
        Validator::make($request->all(), [
            'old_institution_id' => 'required|unique:finicity_oauth_institutions',
            'new_institution_id' => 'required|unique:finicity_oauth_institutions',
            'transition_message' => 'string',
        ])->validate();
        $finicityOauthInstitution = new FinicityOauthInstitution();
        $finicityOauthInstitution->merge($request->all());
        $finicityOauthInstitution->save();
        $finicityOauthInstitution->load(['successful_migrations', 'failed_migrations', 'pending_migrations', 'institution_credentials']);
        $finicityOauthInstitution->appendResourceProperties();
        return new FinicityOauthInstitutionResource($finicityOauthInstitution);
    }

    public function getOauthInstitutions()
    {
        $finicityOauthInstitutions = FinicityOauthInstitution::with([
            'successful_migrations',
            'failed_migrations',
            'pending_migrations',
            'institution_credentials'
        ])->get();
        foreach ($finicityOauthInstitutions as $finicityOauthInstitution) {
            $finicityOauthInstitution->appendResourceProperties();
        }
        return FinicityOauthInstitutionResource::collection($finicityOauthInstitutions);
    }

    public function getOauthInstitution($institution_id)
    {
        $finicityOauthInstitution = FinicityOauthInstitution::with([
            'successful_migrations',
            'failed_migrations',
            'pending_migrations',
            'institution_credentials'
        ])->findOrFail($institution_id);
        $finicityOauthInstitution->appendResourceProperties();
        return new FinicityOauthInstitutionResource($finicityOauthInstitution);
    }

    public function migrateOauthInstitution($oauth_institution_id)
    {
        $finicityOauthInstitution = FinicityOauthInstitution::with('institution_credentials')->findOrFail($oauth_institution_id);

        $finicityOauthInstitution->migrateRemainingUsers();
        $finicityOauthInstitution->refresh();
        $finicityOauthInstitution->appendResourceProperties();
        return new FinicityOauthInstitutionResource($finicityOauthInstitution);
    }
}
