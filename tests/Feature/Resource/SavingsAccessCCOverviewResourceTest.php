<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\SavingsAccessCCOverviewResource as SavingsAccessCCOverviewResourceV1;
use App\Http\Resources\V1\InstitutionAccountOverviewResource as InstitutionAccountOverviewResourceV1;
use App\Http\Resources\V1\TransactionResource as TransactionResourceV1;
use App\Models\BankAccount;
use App\Models\Transaction;

class SavingsAccessCCOverviewResourceTest extends TestCase
{
    public function testSavingsAccessCCOverviewResourceV1Types()
    {
        $testBankAccount = BankAccount::factory()->create([
            'slug' => 'savings_credit_card',
            'type' => 'credit',
        ]);
        // fetch bankaccount through the account to create an instance of the appropriate model through the builder
        $testBankAccount = $testBankAccount->account->bankAccounts()->find($testBankAccount->id);
        $testTransaction = Transaction::factory()->create([
            'bank_account_id' => $testBankAccount->id
        ]);
        // update test model and convert to resource
        $testBankAccount->refresh();
        $testResource = new SavingsAccessCCOverviewResourceV1($testBankAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institution_account'] instanceof InstitutionAccountOverviewResourceV1);
        $this->assertTrue($testResource['unassigned_transactions'][0] instanceof TransactionResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsInt($testResource['account_id']);
        $this->assertIsInt($testResource['institution_account_id']);
        $this->assertIsString($testResource['name']);
        $this->assertIsString($testResource['slug']);
        $this->assertIsString($testResource['type']);
        $this->assertIsString($testResource['color']);
        $this->assertIsString($testResource['icon']);
        $this->assertIsBool($testResource['is_required']);
        $this->assertIsBool($testResource['is_balance_overridden']);
        $this->assertIsString($testResource['online_banking_url']);
        $this->assertIsFloat($testResource['allocation_balance_adjustment']);
        $this->assertIsFloat($testResource['assignment_balance_adjustment']);
        $this->assertIsFloat($testResource['balance_available']);
        $this->assertIsFloat($testResource['balance_current']);
        $this->assertIsFloat($testResource['balance_limit']);
        $this->assertIsInt($testResource['count_of_unassigned_transactions']);
    }
}
