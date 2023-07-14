<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\FinicityRefreshResource as FinicityRefreshResourceV1;
use App\Models\FinicityRefresh;

class FinicityRefreshResourceTest extends TestCase
{
    public function testFinicityRefreshResourceV1Types()
    {
        $testFinicityRefresh = FinicityRefresh::factory()->create();
        // update test model and convert to resource
        $testResource = new FinicityRefreshResourceV1($testFinicityRefresh);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['status']);
        $this->assertIsString($testResource['error']);
    }
}
