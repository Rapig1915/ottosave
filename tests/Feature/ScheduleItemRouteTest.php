<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankAccount;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\BankAccount\ScheduleItem;
use App\Models\User;

class ScheduleItemRouteTest extends TestCase
{
    protected $user;
    protected $bankAccount;
    protected $v1ScheduleItemPayload;

    protected function setUp(): void
    {
        $scheduleItemRouteTest = $this;
        parent::setUp();
        $scheduleItemRouteTest->user = User::factory()->create();
        $scheduleItemRouteTest->user->accounts()->save(Account::factory()->make());
        $scheduleItemRouteTest->bankAccount = BankAccount::factory()->create([
            'account_id' => $scheduleItemRouteTest->user->current_account->id
        ]);

        $scheduleItemRouteTest->v1ScheduleItemPayload = [
            'amount_monthly' => 0,
            'amount_total' => 0,
            'bank_account_id' => $scheduleItemRouteTest->bankAccount->id,
            'description' => '',
            'id' => null,
            'isCalculatingMonthlyAmount' => false,
            'isDirty' => false,
            'isSaving' => false,
            'type' => 'monthly',
            'sourceAppHttpCancelToken' => ['token'=> ['promise' => []]]
        ];

    }
    public function testStoreScheduleItemV1()
    {
        $scheduleItemRouteTest = $this;
        $v1StoredScheduleItemStructure = [
            'amount_monthly',
            'amount_total',
            'bank_account_id',
            'created_at',
            'date_end',
            'description',
            'id',
            'type',
            'updated_at'
        ];

        $response = $scheduleItemRouteTest->actingAs($scheduleItemRouteTest->user, 'api')->withHeaders([
            'current-account-id' => $scheduleItemRouteTest->user->current_account->id
        ])->json('PUT', '/api/v1/account/bank-accounts/schedule-item', $scheduleItemRouteTest->v1ScheduleItemPayload);

        $response
            ->assertStatus(201)
            ->assertJsonStructure($v1StoredScheduleItemStructure);
    }
    public function testCalculateMonthlyAmountV1()
    {
        $scheduleItemRouteTest = $this;
        $v1CalculatedScheduleItemStructure = [
            'amount_monthly',
            'amount_total',
            'bank_account_id',
            'date_end',
            'description',
            'type'
        ];
        $response = $scheduleItemRouteTest->actingAs($scheduleItemRouteTest->user, 'api')->withHeaders([
            'current-account-id' => $scheduleItemRouteTest->user->current_account->id
        ])->json('POST', '/api/v1/account/bank-accounts/schedule-item/calculateMonthlyAmount', $scheduleItemRouteTest->v1ScheduleItemPayload);

        $response
            ->assertOk()
            ->assertJsonStructure($v1CalculatedScheduleItemStructure);
    }
    public function testDestroyV1()
    {
        $scheduleItemRouteTest = $this;
        $scheduleItem = ScheduleItem::factory()->create([
            'bank_account_id' => $scheduleItemRouteTest->bankAccount->id
        ]);
        $scheduleItem = ScheduleItem::find($scheduleItem->id);
        $scheduleItemRouteTest->assertTrue($scheduleItem instanceof ScheduleItem);

        $response = $scheduleItemRouteTest->actingAs($scheduleItemRouteTest->user, 'api')->withHeaders([
            'current-account-id' => $scheduleItemRouteTest->user->current_account->id
        ])->delete("/api/v1/account/bank-accounts/schedule-item/{$scheduleItem->id}");
        $response->assertStatus(204);
        $scheduleItem = ScheduleItem::find($scheduleItem->id);
        $scheduleItemRouteTest->assertTrue(!$scheduleItem);
    }
}
