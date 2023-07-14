<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V2\AdminUserListAccountResource as AdminUserListAccountResourceV2;
use App\Http\Resources\V2\InstitutionResource as InstitutionResourceV2;
use App\Models\Account;

class AdminUserListAccountResourceTest extends TestCase
{
    public function testAdminUserListAccountResourceV2Types()
    {
        // define model properties
        $testAccount = Account::factory()->make([
            'expire_date' => '2019-06-13',
            'status' => 'active',
            'subscription_plan' => 'plus',
            'subscription_provider' => 'braintree',
            'subscription_type' => 'monthly',
            'braintree_customer_id' => 'foobar',
        ]);
        
        // temporarily store in database
        $testAccount->save();
        $testAccount->institutions()->create();
        $testAccount->refresh();
        //convert to resource
        $testResource = new AdminUserListAccountResourceV2($testAccount);
        $testResource = $testResource->toArray(null);
        // test nested resources
        $this->assertTrue($testResource['institutions'][0] instanceof InstitutionResourceV2);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['expire_date']);
        $this->assertIsString($testResource['status']);
        $this->assertIsString($testResource['subscription_plan']);
        $this->assertIsString($testResource['subscription_provider']);
        $this->assertIsString($testResource['subscription_origin']);
        $this->assertIsString($testResource['subscription_type']);
        $this->assertIsString($testResource['braintree_customer_id']);
    }
}
