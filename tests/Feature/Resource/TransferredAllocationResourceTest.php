<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V2\TransferredAllocationResource as TransferredAllocationResourceV2;
use App\Http\Resources\V1\IncomeAccountOverviewResource as IncomeAccountOverviewResourceV1;
use App\Http\Resources\V1\CCPayoffAccountOverviewResource as CCPayoffAccountOverviewResourceV1;
use App\Http\Resources\V1\AllocationAccountResource as AllocationAccountResourceV1;
use App\Http\Resources\V3\LinkedBankAccountResource as LinkedBankAccountResourceV3;
use App\Models\BankAccount;
use App\Models\BankAccount\Allocation;

class TransferredAllocationResourceTest extends TestCase
{
    public function testTransferredAllocationResourceV2Types()
    {
        $testAllocation = Allocation::factory()->create();
        $incomeBankAccount = BankAccount::factory()->create([
            'slug' => 'income_deposit'
        ]);
        $ccPayoffAccount = BankAccount::factory()->create([
            'slug' => 'cc_payoff'
        ]);
        $spendingAccount = BankAccount::factory()->create([
            'slug' => 'everyday_checking'
        ]);
        $primaryCheckingAccount = BankAccount::factory()->create([
            'slug' => 'primary_checking'
        ]);
        $primarySavingAccount = BankAccount::factory()->create([
            'slug' => 'primary_savings'
        ]);
        $testAllocation->bank_account_id = $incomeBankAccount->id;
        $testAllocation->transferred_from_id = $ccPayoffAccount->id;
        $testAllocation->save();
        // update test model and convert to resource
        $testAllocation->refresh();
        $testResource = new TransferredAllocationResourceV2($testAllocation);
        $testResource = $testResource->toArray(null);
        // test nested relations
        $this->assertTrue($testResource['bank_account'] instanceof IncomeAccountOverviewResourceV1);
        $this->assertTrue($testResource['transferred_from_bank_account'] instanceof CCPayoffAccountOverviewResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsInt($testResource['defense_id']);
        $this->assertIsInt($testResource['bank_account_id']);
        $this->assertIsInt($testResource['transferred_from_id']);
        $this->assertIsFloat($testResource['amount']);
        $this->assertIsBool($testResource['transferred']);
        $this->assertIsBool($testResource['cleared']);
        $this->assertIsBool($testResource['cleared_out']);

        // reassign bank account types and test nested relations are of appropriate type
        $testAllocation->bank_account_id = $spendingAccount->id;
        $testAllocation->transferred_from_id = $incomeBankAccount->id;
        $testAllocation->save();
        // update test model and convert to resource
        $testAllocation->refresh();
        $testResource = new TransferredAllocationResourceV2($testAllocation);
        $testResource = $testResource->toArray(null);
        // test nested relations
        $this->assertTrue($testResource['bank_account'] instanceof AllocationAccountResourceV1);
        $this->assertTrue($testResource['transferred_from_bank_account'] instanceof IncomeAccountOverviewResourceV1);

        $testAllocation->bank_account_id = $ccPayoffAccount->id;
        $testAllocation->transferred_from_id = $spendingAccount->id;
        $testAllocation->save();
        // update test model and convert to resource
        $testAllocation->refresh();
        $testResource = new TransferredAllocationResourceV2($testAllocation);
        $testResource = $testResource->toArray(null);
        // test nested relations
        $this->assertTrue($testResource['bank_account'] instanceof CCPayoffAccountOverviewResourceV1);
        $this->assertTrue($testResource['transferred_from_bank_account'] instanceof AllocationAccountResourceV1);

        $testAllocation->bank_account_id = $primaryCheckingAccount->id;
        $testAllocation->transferred_from_id = $primarySavingAccount->id;
        $testAllocation->save();
        // update test model and convert to resource
        $testAllocation->refresh();
        $testResource = new TransferredAllocationResourceV2($testAllocation);
        $testResource = $testResource->toArray(null);
        // test nested relations
        $this->assertTrue($testResource['bank_account'] instanceof LinkedBankAccountResourceV3);
        $this->assertTrue($testResource['transferred_from_bank_account'] instanceof LinkedBankAccountResourceV3);
    }
}
