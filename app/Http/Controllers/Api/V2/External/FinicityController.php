<?php

namespace App\Http\Controllers\Api\V2\External;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\V2\LinkedBankAccountResource;
use App\Services\External\FinicityService;
use App\Http\Resources\V2\FinicityConnectLinkResource;
use App\Jobs\CreateFinicityInstitutionAccounts;

class FinicityController extends Controller
{
    public function createFinicityInstitutionAccounts(Request $request)
    {
        $finicityCustomer = Auth::user()->current_account->finicity_customer;
        if ($finicityCustomer) {
            $createdBankAccounts = CreateFinicityInstitutionAccounts::dispatchSync($finicityCustomer);
            if(empty($createdBankAccounts)) {
                return [];
            } else {
                return LinkedBankAccountResource::collection(collect($createdBankAccounts));
            }
        } else {
            abort(403, "Account not authorized for this request.");
        }
    }

    public function getFinicityConnectLink(Request $request, $institution_id)
    {
        $finicityService = new FinicityService();
        $type = $request->query('type');
        $excludeRedirectLinkQuery = $request->query('exclude_redirect_link', false);
        $excludeRedirectLink = !$excludeRedirectLinkQuery || $excludeRedirectLinkQuery !== 'false';
        if ($type === 'fix') {
            $institution = Auth::user()->current_account->institutions()->findOrFail($institution_id);
            $customerId = Auth::user()->current_account->finicity_customer->customer_id;
            $connectLink = $finicityService->getConnectFixV2Link($customerId, $institution->credentials->remote_secret, $excludeRedirectLink);
        } else {
            $connectLink = $finicityService->getConnectV2Link(Auth::user(), $excludeRedirectLink);
        }
        return new FinicityConnectLinkResource($connectLink);
    }
}
