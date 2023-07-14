<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\External\FinicityService;
use App\Models\BankAccount;
use App\Models\Institution;
use App\Events\InstitutionCreated;
use App\Events\CreditCardLinked;
use Carbon\Carbon;

class FinicityCustomer extends Model
{
    protected $table = 'finicity_customers';
    protected $fillable = [
        'customer_id',
        'username'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($finicityCustomer) {
            $finicityCustomer->deleteFinicityCustomer();
        });
    }

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function finicity_refreshes()
    {
        return $this->morphMany('App\Models\FinicityRefresh', 'finicity_refreshable');
    }

    public function latest_finicity_refresh()
    {
        return $this->morphOne('App\Models\FinicityRefresh', 'finicity_refreshable')->latest();
    }

    public function deleteFinicityCustomer()
    {
        $finicityCustomer = $this;
        $finicityService = new FinicityService();
        $finicityService->deleteCustomer(null, $finicityCustomer->customer_id);
        $finicityInstitutions = $finicityCustomer->account->institutions()->where('type', 'finicity')->get();
        foreach ($finicityInstitutions as $finicityInstitution) {
            $finicityInstitution->delete();
        }
    }

    public function createLinkedAccount($finicityAccount)
    {
        $finicityCustomer = $this;
        try {
            $institutionAccount = $finicityCustomer->createFinicityInstitutionAccount($finicityAccount);
            $institutionAccount->linked_at = Carbon::now();
            $institutionAccount->save();
            $bankAccountPayload = [
                'name' => '',
                'purpose' => 'unassigned',
                'color' => ''
            ];
            $bankAccount = BankAccount::mergeOrCreate($bankAccountPayload);
            $bankAccount->institution_account_id = $institutionAccount->id;
            $bankAccount->save();
            if ($institutionAccount->subtype === 'creditCard') {
                event(new CreditCardLinked($institutionAccount));
            }
            $bankAccount->refresh();
            return $bankAccount;
        } catch (\Exception $e) {
            abort(500, "Failed to link account");
        }
    }

    public function createFinicityInstitutionAccount($finicityAccount)
    {
        $finicityCustomer = $this;
        $institutionAccount = new InstitutionAccount();
        $institutionAccount->name = $finicityAccount->name ?? '';
        $institutionAccount->remote_id = $finicityAccount->id ?? '';
        $institutionAccount->subtype = $finicityAccount->type ?? '';
        $institutionAccount->mask = substr($finicityAccount->number, -4);
        $institutionAccount->iso_currency_code = $finicityAccount->currency ?? '';
        $institutionAccount->balance_current = $finicityAccount->balance ?? 0;

        $relatedInstitution = $finicityCustomer->account->institutions()->whereHas('credentials', function ($query) use ($finicityAccount) {
            $query->where('remote_id', '=', $finicityAccount->institutionId);
        })->first();
        if (!$relatedInstitution) {
            $relatedInstitution = $finicityCustomer->createFinicityInstitution($finicityAccount->institutionId, $finicityAccount->institutionLoginId);
        }
        $relatedInstitution->institutionAccount()->save($institutionAccount);
        return $institutionAccount;
    }

    public function createFinicityInstitution($institutionId, $institutionLoginId)
    {
        $finicityCustomer = $this;
        $finicityService = new FinicityService();
        $finicityInstitution = $finicityService->getInstitution($institutionId);

        $institution = new Institution();
        $institution->account_id = $finicityCustomer->account_id;
        $institution->type = 'finicity';
        $institution->name = $finicityInstitution->name;
        $institution->save();
        $institution->credentials()->create([
            'remote_id' => $finicityInstitution->id,
            'remote_secret' => $institutionLoginId
        ]);
        event(new InstitutionCreated($institution));
        return $institution;
    }

    public function getOrCreatePendingFinicityRefresh()
    {
        $finicityCustomer = $this;
        $finicityRefresh = $finicityCustomer->finicity_refreshes()->where('status', '=', 'pending')->latest()->first();
        if (!$finicityRefresh) {
            $finicityRefresh = $finicityCustomer->finicity_refreshes()->create([
                'status' => 'pending',
                'error' => ''
            ]);
        }
        return $finicityRefresh;
    }
}
