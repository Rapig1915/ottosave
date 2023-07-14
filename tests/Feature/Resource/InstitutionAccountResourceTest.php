<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V2\InstitutionAccountResource as InstitutionAccountResourceV2;
use App\Http\Resources\V2\InstitutionResource as InstitutionResourceV2;
use App\Models\InstitutionAccount;

class InstitutionAccountResourceTest extends TestCase
{
    public function testInstitutionAccountResourceV2Types()
    {
        $testInstitutionAccount = InstitutionAccount::factory()->create();
        // update test model and convert to resource
        $testInstitutionAccount->refresh();
        $testResource = new InstitutionAccountResourceV2($testInstitutionAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institution'] instanceof InstitutionResourceV2);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['linked_at']);
        $this->assertIsString($testResource['mask']);
        $this->assertIsString($testResource['subtype']);
        $this->assertIsString($testResource['name']);
        $this->assertIsFloat($testResource['balance_available']);
        $this->assertIsFloat($testResource['balance_current']);
        $this->assertIsFloat($testResource['balance_limit']);
        $this->assertIsInt($testResource['institution_id']);
        $this->assertIsString($testResource['iso_currency_code']);
        $this->assertIsString($testResource['remote_id']);
        $this->assertIsString($testResource['remote_status_code']);
        $this->assertIsString($testResource['api_status']);
        $this->assertIsString($testResource['api_status_message']);
    }
}
