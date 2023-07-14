<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\TransactionResource as TransactionResourceV1;
use App\Models\BankAccount\Allocation;
use App\Models\Transaction;

class TransactionResourceTest extends TestCase
{
    public function testTransactionResourceV1Types()
    {
        $testTransaction = Transaction::factory()->create();
        $testParentTransaction = Transaction::factory()->create();
        $testAllocation = Allocation::factory()->create();
        $testTransaction->allocation_id = $testAllocation->id;
        $testTransaction->parent_transaction_id = $testParentTransaction->id;
        $testTransaction->save();
        // update test model and convert to resource
        $testTransaction->refresh();
        $testResource = new TransactionResourceV1($testTransaction);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsInt($testResource['bank_account_id']);
        $this->assertIsInt($testResource['allocation_id']);
        $this->assertIsFloat($testResource['amount']);
        $this->assertIsInt($testResource['is_assignable']);
        $this->assertIsString($testResource['action_type']);
        $this->assertIsString($testResource['merchant']);
        $this->assertIsString($testResource['remote_account_id']);
        $this->assertIsString($testResource['remote_category']);
        $this->assertIsString($testResource['remote_category_id']);
        $this->assertIsString($testResource['remote_merchant']);
        $this->assertIsString($testResource['remote_transaction_date']);
        $this->assertIsString($testResource['remote_transaction_id']);
        $this->assertIsInt($testResource['parent_transaction_id']);
    }
}
