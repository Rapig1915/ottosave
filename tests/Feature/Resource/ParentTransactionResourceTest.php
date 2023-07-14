<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\ParentTransactionResource as ParentTransactionResourceV1;
use App\Http\Resources\V1\SplitTransactionResource as SplitTransactionResourceV1;
use App\Models\Transaction;

class ParentTransactionResourceTest extends TestCase
{
    public function testParentTransactionResourceV1Types()
    {
        $testTransaction = Transaction::factory()->create();
        $testSplitTransaction = Transaction::factory()->create();
        $testTransaction->splitTransactions()->save($testSplitTransaction);
        // update test model and convert to resource
        $testTransaction->refresh();
        $testResource = new ParentTransactionResourceV1($testTransaction);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsInt($testResource['bank_account_id']);
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
        // test resource relations
        $this->assertTrue($testResource['split_transactions'][0] instanceof SplitTransactionResourceV1);
    }
}
