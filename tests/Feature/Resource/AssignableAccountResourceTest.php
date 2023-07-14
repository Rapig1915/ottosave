<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\AssignmentResource as AssignmentResourceV1;
use App\Http\Resources\V2\AssignableAccountResource as AssignableAccountResourceV2;
use App\Http\Resources\V2\InstitutionAccountResource as InstitutionAccountResourceV2;
use App\Models\Assignment;
use App\Models\BankAccount;

class AssignableAccountResourceTest extends TestCase
{
    public function testAssignableAccountResourceV2Types()
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
        $testAssignment = Assignment::factory()->create([
            'bank_account_id' => $testBankAccount->id
        ]);
        // update test model and convert to resource
        $testBankAccount->refresh();
        $testResource = new AssignableAccountResourceV2($testBankAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institution_account'] instanceof InstitutionAccountResourceV2);
        $this->assertTrue($testResource['untransferred_assignments'][0] instanceof AssignmentResourceV1);
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
    }
}
