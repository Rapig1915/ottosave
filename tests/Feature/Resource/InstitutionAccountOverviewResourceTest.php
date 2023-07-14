<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\InstitutionAccountOverviewResource as InstitutionAccountOverviewResourceV1;
use App\Models\InstitutionAccount;

class InstitutionAccountOverviewResourceTest extends TestCase
{
    public function testInstitutionAccountOverviewResourceV1Types()
    {
        $testInstitutionAccount = InstitutionAccount::factory()->create();
        // update test model and convert to resource
        $testInstitutionAccount->refresh();
        $testResource = new InstitutionAccountOverviewResourceV1($testInstitutionAccount);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['mask']);
        $this->assertIsString($testResource['subtype']);
        $this->assertIsString($testResource['name']);
        $this->assertIsString($testResource['official_name']);
        $this->assertIsFloat($testResource['balance_available']);
        $this->assertIsFloat($testResource['balance_current']);
        $this->assertIsFloat($testResource['balance_limit']);
        $this->assertIsInt($testResource['institution_id']);
        $this->assertIsString($testResource['iso_currency_code']);
        $this->assertIsString($testResource['remote_id']);
    }
}
