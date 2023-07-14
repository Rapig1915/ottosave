<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\AssignmentResource as AssignmentResourceV1;
use App\Http\Resources\V1\TransactionResource as TransactionResourceV1;
use App\Http\Resources\V1\AllocationResource as AllocationResourceV1;
use App\Models\Assignment;
use App\Models\BankAccount\Allocation;

class AssignmentResourceTest extends TestCase
{
    public function testAssignmentResourceV1Types()
    {
        $testAssignment = Assignment::factory()->create();
        $testAllocation = Allocation::factory()->create([
            'amount' => ($testAssignment->transaction->amount / 2),
            'transferred' => true
        ]);
        $testAssignment->allocations()->save($testAllocation);
        // update test model and convert to resource
        $testAssignment->refresh();
        $testAssignment->loadMissing('allocations');
        $testResource = new AssignmentResourceV1($testAssignment);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['transaction'] instanceof TransactionResourceV1);
        $this->assertTrue($testResource['allocations'][0] instanceof AllocationResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsInt($testResource['bank_account_id']);
        $this->assertIsInt($testResource['transaction_id']);
        $this->assertIsBool($testResource['transferred']);
        $this->assertIsFloat($testResource['allocated_amount']);
    }
}
