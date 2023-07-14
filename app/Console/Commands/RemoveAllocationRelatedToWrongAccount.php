<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Account;

class RemoveAllocationRelatedToWrongAccount extends Command
{

    protected $signature = 'dym:remove-invalid-allocations';
    protected $description = 'One time fix for allocations created incorrectly by a broken relationship.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            DB::beginTransaction();

            $accounts = Account::get();
            foreach ($accounts as $account) {
                $bankAccountIds = collect($account->bankAccounts)->pluck('id')->toArray();
                foreach ($account->defenses as $defense) {
                    $defense->allocation()->whereNotIn('bank_account_id', $bankAccountIds)->delete();
                }
            }

            DB::commit();
            echo "Successfully deleted invalid allocations\n";
        } catch (\Exception $e) {
            DB::rollback();
            echo "Failed to delete invalid allocations\n";
            echo $e->getMessage();
            echo "\n";
        }
    }
}
