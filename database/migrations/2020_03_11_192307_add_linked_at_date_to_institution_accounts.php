<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLinkedAtDateToInstitutionAccounts extends Migration
{
    public function up()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->timestamp('linked_at')->after('updated_at')->nullable();
        });
        $linkedInstitutionAccounts = DB::table('institution_accounts')
            ->join('bank_accounts', 'institution_accounts.id', '=', 'bank_accounts.institution_account_id')
            ->select('institution_accounts.*')
            ->get();
        foreach ($linkedInstitutionAccounts as $institutionAccount) {
            DB::table('institution_accounts')
                ->where('id', $institutionAccount->id)
                ->update([
                    'linked_at' => $institutionAccount->created_at
                ]);
        }
    }

    public function down()
    {
        Schema::table('institution_accounts', function(Blueprint $table) {
            $table->dropColumn('linked_at');
        });
    }
}
