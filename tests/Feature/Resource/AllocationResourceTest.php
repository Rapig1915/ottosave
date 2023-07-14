<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\AllocationResource as AllocationResourceV1;
use App\Http\Resources\V1\AssignmentResource as AssignmentResourceV1;
use App\Models\Assignment;
use App\Models\BankAccount\Allocation;

class AllocationResourceTest extends TestCase
{
    public function testAllocationResourceV1Types()
    {
        $testAllocation = Allocation::factory()->create();
        $testAssignment = Assignment::factory()->create([
            'bank_account_id' => $testAllocation->bank_account_id
        ]);
        $testAllocation->assignments()->save($testAssignment);
        // update test model and convert to resource
        $testAllocation->refresh();
        $testAllocation->loadMissing('assignments');
        $testResource = new AllocationResourceV1($testAllocation);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['assignments'][0] instanceof AssignmentResourceV1);
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
    }
}
