<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\Institution;

class RemoveDuplicateInstitutions extends Command
{
    protected $signature = 'dym:remove-duplicate-institutions';
    protected $description = 'Institutions duplicated for a user account are deleted';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $numberOfRemovedInstitutions = 0;
        $accounts = Account::with('institutions', 'institutions.credentials')->get();
        foreach ($accounts as $account) {
            $institutionsByRemoteId = $account->institutions->groupBy('credentials.remote_id');
            $institutionsWithDuplicates = $institutionsByRemoteId->filter(function ($institutions, $remote_id) {
                return $institutions->count() > 1;
            });
            $institutionIdsToDestroy = $institutionsWithDuplicates->map(function ($institutions, $remote_id) {
                return $institutions->slice(1)->pluck('id')->values();
            })->flatten();
            $countOfDuplicates = $institutionIdsToDestroy->count();
            if ($countOfDuplicates > 0) {
                echo "Removing {$institutionIdsToDestroy->count()} institutions...\n";
                $numberOfRemovedInstitutions += $institutionIdsToDestroy->count();
                Institution::destroy($institutionIdsToDestroy);
            }
        }
        echo "A total of {$numberOfRemovedInstitutions} duplcate institutions were found and removed\n";
    }
}
