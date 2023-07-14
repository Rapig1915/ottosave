<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\InstitutionAccount;
use App\Models\BankAccount;
use Carbon\Carbon;

class LinkOrphanedInstitutionAccounts extends Command
{

    protected $signature = 'dym:link-orphan-institution-accounts';
    protected $description = 'Creates a bank account related to any institution accounts that do not have one.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $institutionAccounts = InstitutionAccount::whereDoesntHave('bankAccount')->with(['institution'])->get();
        foreach ($institutionAccounts as $institutionAccount) {
            try {
                DB::beginTransaction();
                $bankAccountPayload = [
                    'name' => '',
                    'account_id' => $institutionAccount->institution->account_id,
                    'slug' => null,
                    'type' => null,
                    'color' => '',
                    'icon' => 'square',
                    'appears_in_account_list' => true
                ];
                $bankAccount = BankAccount::create($bankAccountPayload);
                $bankAccount->institution_account_id = $institutionAccount->id;
                $bankAccount->save();
                $bankAccount->refresh();
                $institutionAccount->linked_at = Carbon::now();
                $institutionAccount->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                echo "Failed to create bank account for institution account with id: {$institutionAccount->id}\n";
                echo $e->getMessage();
                echo "\n";
            }
        }
    }
}
