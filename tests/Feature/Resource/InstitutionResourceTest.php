<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V2\InstitutionResource as InstitutionResourceV2;
use App\Models\Institution;

class InstitutionResourceTest extends TestCase
{
    public function testInstitutionResourceV2Types()
    {
        $testInstitution = Institution::factory()->create();
        $testResource = new InstitutionResourceV2($testInstitution);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsInt($testResource['account_id']);
        $this->assertIsString($testResource['name']);
        $this->assertIsString($testResource['type']);
        $this->assertIsString($testResource['oauth_transition_message']);
    }
}
