<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\SpendingAccountOverviewResource as SpendingAccountOverviewResourceV1;
use App\Http\Resources\V1\InstitutionAccountOverviewResource as InstitutionAccountOverviewResourceV1;
use App\Models\BankAccount;

class SpendingAccountOverviewResourceTest extends TestCase
{
    public function testSpendingAccountOverviewResourceV1Types()
    {
        $testParentBankAccount = BankAccount::factory()->create([
            'slug' => 'primary_checking',
            'type' => 'checking',
        ]);
        $testBankAccount = BankAccount::factory()->create([
            'slug' => 'everyday_checking',
            'type' => 'checking',
            'account_id' => $testParentBankAccount->account_id,
            'parent_bank_account_id' => $testParentBankAccount->id
        ]);
        // fetch bankaccount through the account to create an instance of the appropriate model through the builder
        $testBankAccount = $testBankAccount->account->bankAccounts()->find($testBankAccount->id);
        // update test model and convert to resource
        $testBankAccount->refresh();
        $testResource = new SpendingAccountOverviewResourceV1($testBankAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institution_account'] instanceof InstitutionAccountOverviewResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsInt($testResource['account_id']);
        $this->assertIsInt($testResource['institution_account_id']);
        $this->assertIsInt($testResource['parent_bank_account_id']);
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
        $this->assertIsFloat($testResource['spent_after_most_recent_defense']);
    }
}
