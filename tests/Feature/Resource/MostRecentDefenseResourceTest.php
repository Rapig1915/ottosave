<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\MostRecentDefenseResource as MostRecentDefenseResourceV1;
use App\Http\Resources\V1\AllocationResource as AllocationResourceV1;
use App\Models\BankAccount\Allocation;

class MostRecentDefenseResourceTest extends TestCase
{
    public function testMostRecentDefenseResourceV1Types()
    {
        $testAllocation = Allocation::factory()->create();
        // update test model and convert to resource
        $testAllocation->refresh();
        $testResource = new MostRecentDefenseResourceV1($testAllocation->defense);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['allocation'][0] instanceof AllocationResourceV1);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['end_date']);
        $this->assertIsInt($testResource['account_id']);
        $this->assertIsFloat($testResource['everyday_checking_starting_balance']);
        $this->assertIsBool($testResource['is_current']);
    }
}
