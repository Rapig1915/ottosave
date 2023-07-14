<?php

namespace App\Http\Controllers\Api\V1\Authorized;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\V2\InstitutionResource;
use App\Events\InstitutionAccountCountChanged;

class InstitutionController extends Controller
{
    public function getInstitutionsToMigrate()
    {
        $institutionsToMigrate = Auth::user()->current_account->institutions()
            ->has('credentials.finicity_oauth_institution')
            ->with(['credentials', 'credentials.finicity_oauth_institution'])
            ->get();

        return InstitutionResource::collection($institutionsToMigrate);
    }

    public function migrateInstitutionToOauth($institution_id)
    {
        $institution = Auth::user()->current_account->institutions()
            ->has('credentials.finicity_oauth_institution')
            ->with([
                'credentials',
                'credentials.finicity_oauth_institution'
            ])
            ->where('id', $institution_id)
            ->firstOrFail();
        $synchronousOperation = true;
        $institution->credentials->finicity_oauth_institution->dispatchMigration($institution->credentials, $synchronousOperation);
        return response()->json(null, 204);
    }

    public function deleteInstitution($institution_id)
    {
        $currentAccount = Auth::user()->current_account;
        $institution = $currentAccount->institutions()->with(
            'credentials',
            'account',
            'account.finicity_customer'
        )->findOrFail($institution_id);
        $institution->deleteRemoteInstitution();
        $institution->delete();
        event(new InstitutionAccountCountChanged($currentAccount));
        return response()->json(null, 204);
    }
}
