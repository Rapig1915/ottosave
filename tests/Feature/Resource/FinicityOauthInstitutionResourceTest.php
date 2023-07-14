<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\FinicityOauthInstitutionResource as FinicityOauthInstitutionResourceV1;
use App\Models\FinicityOauthInstitution;

class FinicityOauthInstitutionResourceTest extends TestCase
{
    public function testFinicityConnectLinkResourceV1Types()
    {
        $finicityOauthInstitution = FinicityOauthInstitution::factory()->create();
        $testResource = new FinicityOauthInstitutionResourceV1($finicityOauthInstitution);
        $testResource = $testResource->toArray(null);

        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['old_institution_id']);
        $this->assertIsString($testResource['new_institution_id']);
        $this->assertIsString($testResource['transition_message']);
        $this->assertIsInt($testResource['number_of_institutions_to_migrate']);
        $this->assertIsInt($testResource['number_of_successful_migrations']);
        $this->assertIsInt($testResource['number_of_failed_migrations']);
        $this->assertIsInt($testResource['number_of_pending_migrations']);
    }
}
