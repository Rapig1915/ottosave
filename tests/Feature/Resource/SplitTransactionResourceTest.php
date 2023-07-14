<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\SplitTransactionResource as SplitTransactionResourceV1;
use App\Http\Resources\V1\AssignmentResource as AssignmentResourceV1;
use App\Models\Assignment;
use App\Models\Transaction;

class SplitTransactionResourceTest extends TestCase
{
    public function testSplitTransactionResourceV1Types()
    {
        $testTransaction = Transaction::factory()->create();
        $testParentTransaction = Transaction::factory()->create();
        $testAssignment = Assignment::factory()->create();
        $testTransaction->assignment()->save($testAssignment);
        $testTransaction->parent_transaction_id = $testParentTransaction->id;
        $testTransaction->save();
        // update test model and convert to resource
        $testTransaction->refresh();
        $testResource = new SplitTransactionResourceV1($testTransaction);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsInt($testResource['bank_account_id']);
        $this->assertIsFloat($testResource['amount']);
        $this->assertIsInt($testResource['is_assignable']);
        $this->assertIsString($testResource['merchant']);
        $this->assertIsString($testResource['remote_transaction_date']);
        $this->assertIsInt($testResource['parent_transaction_id']);
        // test resource relations
        $this->assertTrue($testResource['assignment'] instanceof AssignmentResourceV1);
    }
}
