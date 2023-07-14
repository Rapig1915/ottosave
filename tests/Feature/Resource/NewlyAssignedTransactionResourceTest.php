<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\NewlyAssignedTransactionResource as NewlyAssignedTransactionResourceV1;
use App\Http\Resources\V1\TransactionResource as TransactionResourceV1;
use App\Models\Assignment;

class NewlyAssignedTransactionResourceTest extends TestCase
{
    public function testNewlyAssignedTransactionResourceV1Types()
    {
        $testAssignment = Assignment::factory()->create();
        // update test model and convert to resource
        $testAssignment->refresh();
        $testResource = new NewlyAssignedTransactionResourceV1($testAssignment, $testAssignment->bankAccount->balance_available);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['assignment']['transaction'] instanceof TransactionResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['assignment']['id']);
        $this->assertIsString($testResource['assignment']['created_at']);
        $this->assertIsString($testResource['assignment']['updated_at']);
        $this->assertIsInt($testResource['assignment']['transaction_id']);
        $this->assertIsInt($testResource['assignment']['bank_account_id']);
        $this->assertIsFloat($testResource['assignment']['allocated_amount']);
        $this->assertIsBool($testResource['assignment']['transferred']);
        $this->assertIsFloat($testResource['updated_balance']);
    }
}
