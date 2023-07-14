<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\DefenseResource as DefenseResourceV1;
use App\Http\Resources\V1\AllocationResource as AllocationResourceV1;
use App\Models\BankAccount\Allocation;
use App\Models\Defense;

class DefenseResourceTest extends TestCase
{
    public function testDefenseResourceV1Types()
    {
        $testDefense = Defense::factory()->create();
        $testAllocation = Allocation::factory()->create();
        $testDefense->allocation()->save($testAllocation);
        // update test model and convert to resource
        $testDefense->refresh();
        $testDefense->loadMissing('allocation');
        $testResource = new DefenseResourceV1($testDefense);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['allocations'][0] instanceof AllocationResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['end_date']);
        $this->assertIsInt($testResource['account_id']);
        $this->assertIsFloat($testResource['everyday_checking_starting_balance']);
        $this->assertIsBool($testResource['is_current']);
        $this->assertTrue($testResource['balance_snapshots'] instanceof \stdClass);
    }
}
