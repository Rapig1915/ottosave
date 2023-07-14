<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\TransactionResource as TransactionResourceV1;
use App\Http\Resources\V2\SavingsAccessCCResource as SavingsAccessCCResourceV2;
use App\Http\Resources\V2\InstitutionAccountResource as InstitutionAccountResourceV2;
use App\Models\BankAccount;
use App\Models\Transaction;

class SavingsAccessCCResourceTest extends TestCase
{
    public function testSavingsAccessCCResourceV2Types()
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
        $testResource = new SavingsAccessCCResourceV2($testBankAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institution_account'] instanceof InstitutionAccountResourceV2);
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
        $this->assertIsFloat($testResource['balance_current']);
        $this->assertIsFloat($testResource['balance_limit']);
    }
}
