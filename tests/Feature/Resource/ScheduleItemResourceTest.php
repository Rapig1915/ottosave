<?php

namespace Tests\Feature\Resource;

use Tests\TestCase;
use App\Http\Resources\V1\ScheduleItemResource as ScheduleItemResourceV1;
use App\Models\BankAccount\ScheduleItem;

class ScheduleItemResourceTest extends TestCase
{
    public function testScheduleItemResourceV1Types()
    {
        $testScheduleItem = ScheduleItem::factory()->create();
        // update test model and convert to resource
        $testScheduleItem->refresh();
        $testResource = new ScheduleItemResourceV1($testScheduleItem);
        $testResource = $testResource->toArray(null);
        // test resource property types
        $this->assertIsInt($testResource['id']);
        $this->assertIsString($testResource['created_at']);
        $this->assertIsString($testResource['updated_at']);
        $this->assertIsString($testResource['date_end']);
        $this->assertIsInt($testResource['bank_account_id']);
        $this->assertIsFloat($testResource['amount_monthly']);
        $this->assertIsFloat($testResource['amount_total']);
        $this->assertIsString($testResource['description']);
        $this->assertIsString($testResource['approximate_due_date']);
        $this->assertIsString($testResource['type']);
    }
}
