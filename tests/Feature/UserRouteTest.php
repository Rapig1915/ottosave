<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notifications\EmailVerification;
use App\Models\User;

class UserRouteTest extends TestCase
{
    protected $user;
    protected $v1CurrentAccountStructure;
    protected $v1CurrentAccountUserStructure;
    protected $v1UserStructure;

    protected function setUp(): void
    {
        $userRouteTest = $this;
        parent::setUp();
        $userRouteTest->user = User::factory()->create();
        $userRouteTest->user->accounts()->create();
        $userRouteTest->user->password = 'foobar1';
        $userRouteTest->user->save();
        $userRouteTest->v1CurrentAccountStructure = [
            'braintree_customer_id',
            'created_at',
            'expire_date',
            'id',
            'most_recent_defense',
            'status',
            'subscription_plan',
            'subscription_type',
            'updated_at',
        ];
        $userRouteTest->v1CurrentAccountUserStructure = [
            'account_id',
            'all_permission_names',
            'all_role_names',
            'id',
            'user_id',
        ];
        $userRouteTest->v1UserStructure = [
            'id',
            'created_at',
            'updated_at',
            'email',
            'name',
            'current_account' => $userRouteTest->v1CurrentAccountStructure,
            'current_account_user' => $userRouteTest->v1CurrentAccountUserStructure
        ];
    }

    public function testGetUserV1()
    {
        $userRouteTest = $this;
        $response = $userRouteTest->actingAs($userRouteTest->user, 'api')->get('/api/v1/user');
        $response
            ->assertOk()
            ->assertJsonStructure(['user' => $userRouteTest->v1UserStructure]);
        $responseData = $response->getData();
        $userRouteTest->assertTrue(is_array($responseData->user->current_account_user->all_permission_names));
        $userRouteTest->assertTrue(is_array($responseData->user->current_account_user->all_role_names));
    }

    public function testStoreUserV1()
    {
        $userRouteTest = $this;
        $response = $userRouteTest->actingAs($userRouteTest->user, 'api')->json('PUT', '/api/v1/user', $userRouteTest->user->getAttributes());
        $response
            ->assertOk()
            ->assertJsonStructure($userRouteTest->v1UserStructure);
        $responseData = $response->getData();
        $userRouteTest->assertTrue(is_array($responseData->current_account_user->all_permission_names));
        $userRouteTest->assertTrue(is_array($responseData->current_account_user->all_role_names));
    }

    public function testChangeEmailV1()
    {
        $userRouteTest = $this;
        Mail::fake();
        $response = $userRouteTest->actingAs($userRouteTest->user, 'api')
            ->json('PUT', '/api/v1/user/change-email', [
                'current_password' => 'foobar1',
                'email' => 'test@test.com'
            ]);
        $response->assertStatus(204);
        $userRouteTest->assertTrue($userRouteTest->user->email === 'test@test.com');
        Mail::assertQueued(EmailVerification::class, function ($mail) use ($userRouteTest) {
           return $mail->hasTo($userRouteTest->user->email);
       });
    }

    public function testChangePasswordV1()
    {
        $userRouteTest = $this;
        $response = $userRouteTest->actingAs($userRouteTest->user, 'api')
            ->json('PUT', '/api/v1/user/change-password', [
                'current_password' => 'foobar1',
                'password' => 'fiddledeedee1',
                'password_confirmation' => 'fiddledeedee1'
            ]);
        $response->assertStatus(204);
        $userRouteTest->assertTrue($userRouteTest->user->isCurrentPassword('fiddledeedee1'));
    }
}
