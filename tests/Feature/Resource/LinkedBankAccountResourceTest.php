<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V2\LinkedBankAccountResource as LinkedBankAccountResourceV2;
use App\Http\Resources\V3\LinkedBankAccountResource as LinkedBankAccountResourceV3;
use App\Http\Resources\V2\InstitutionAccountResource as InstitutionAccountResourceV2;
use App\Models\BankAccount;

class LinkedBankAccountResourceTest extends TestCase
{
    public function testLinkedBankAccountResourceV2Types()
    {
        $testBankAccount = BankAccount::factory()->create([
            'slug' => 'everyday_checking',
            'type' => 'checking',
        ]);
        // fetch bankaccount through the account to create an instance of the appropriate model through the builder
        $testBankAccount = $testBankAccount->account->bankAccounts()->find($testBankAccount->id);
        // update test model and convert to resource
        $testBankAccount->refresh();
        $testResource = new LinkedBankAccountResourceV2($testBankAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institution_account'] instanceof InstitutionAccountResourceV2);
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
        $this->assertIsString($testResource['purpose']);
        $this->assertIsBool($testResource['is_required']);
        $this->assertIsBool($testResource['is_balance_overridden']);
        $this->assertIsBool($testResource['appears_in_account_list']);
        $this->assertIsString($testResource['online_banking_url']);
        $this->assertIsFloat($testResource['allocation_balance_adjustment']);
        $this->assertIsFloat($testResource['assignment_balance_adjustment']);
        $this->assertIsFloat($testResource['balance_available']);
        $this->assertIsFloat($testResource['balance_current']);
        $this->assertIsFloat($testResource['balance_limit']);
    }
    public function testLinkedBankAccountResourceV3Types()
    {
        $testBankAccount = BankAccount::factory()->create([
            'slug' => 'everyday_checking',
            'type' => 'checking',
        ]);
        $testSubAccount = BankAccount::factory()->create([
            'parent_bank_account_id' => $testBankAccount->id,
            'account_id' => $testBankAccount->account->id,
            'institution_account_id' => null
        ]);
        // fetch bankaccount through the account to create an instance of the appropriate model through the builder
        $testBankAccount = $testBankAccount->account->bankAccounts()->find($testBankAccount->id);
        // update test model and convert to resource
        $testBankAccount->refresh();
        $testResource = new LinkedBankAccountResourceV3($testBankAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institution_account'] instanceof InstitutionAccountResourceV2);
        $this->assertTrue($testResource['sub_accounts'][0] instanceof LinkedBankAccountResourceV3);

        // test resource property types
        $this->assertTrue($testResource['parent_bank_account_id'] === null);
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
        $this->assertIsString($testResource['purpose']);
        $this->assertIsBool($testResource['is_required']);
        $this->assertIsBool($testResource['is_balance_overridden']);
        $this->assertIsBool($testResource['appears_in_account_list']);
        $this->assertIsString($testResource['online_banking_url']);
        $this->assertIsFloat($testResource['allocation_balance_adjustment']);
        $this->assertIsFloat($testResource['assignment_balance_adjustment']);
        $this->assertIsFloat($testResource['balance_available']);
        $this->assertIsFloat($testResource['balance_current']);
        $this->assertIsInt($testResource['sub_account_order']);
        $this->assertIsFloat($testResource['balance_limit']);
        $this->assertIsFloat($testResource['balance_limit_override']);
    }
}
