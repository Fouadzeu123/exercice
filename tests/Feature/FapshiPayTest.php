<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FapshiPayTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_requires_authenticated_user()
    {
        $response = $this->post(route('wallet.deposit'), [
            'amount' => 5000,
            'method' => 'orange',
            'phone' => '694196055',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_deposit_validation_min_amount()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('wallet.deposit'), [
            'amount' => 10, // below min deposit
            'method' => 'orange',
            'phone' => '694196055',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_deposit_success_redirects_to_custom_pay_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config([
            'services.fapshi.api_user' => 'test-api-user',
            'services.fapshi.api_key' => 'test-api-key',
        ]);

        Http::fake([
            'https://sandbox.fapshi.com/initiate-pay' => Http::response([
                'message' => 'Payment initiated successfully',
                'transId' => 'trans-123',
                'link' => 'https://sandbox.fapshi.com/pay/checkout-link',
            ], 200),
        ]);

        $response = $this->post(route('wallet.deposit'), [
            'amount' => 5000,
            'method' => 'mtn',
            'phone' => '670000000',
        ]);

        $transaction = Transaction::where('user_id', $user->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('trans-123', $transaction->gateway_ref);

        $response->assertRedirect('https://sandbox.fapshi.com/pay/checkout-link');
    }

    public function test_fapshi_webhook_verifies_signature_and_completes_payment()
    {
        $user = User::factory()->create([
            'balance' => 0,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 10000.00,
            'type' => 'deposit',
            'status' => 'pending',
            'reference' => 'DEP-FAPSHI123',
            'payment_method' => 'mtn',
            'payment_phone' => '670000000',
        ]);

        $secret = 'fapshi-webhook-secret';
        config(['services.fapshi.webhook_secret' => $secret]);

        $payload = [
            'transId' => 'trans-123',
            'externalId' => 'DEP-FAPSHI123',
            'status' => 'SUCCESSFUL',
            'amount' => 10000,
        ];

        $response = $this->postJson(route('webhook.fapshi'), $payload, [
            'x-wh-secret' => $secret,
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'OK']);

        $transaction->refresh();
        $user->refresh();

        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals(10000, $user->balance);
    }

    public function test_deposit_fails_when_keys_are_missing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config([
            'services.fapshi.api_user' => null,
            'services.fapshi.api_key' => null,
        ]);

        $response = $this->post(route('wallet.deposit'), [
            'amount' => 5000,
            'method' => 'mtn',
            'phone' => '670000000',
        ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_deposit_fails_when_api_call_fails()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config([
            'services.fapshi.api_user' => 'test-api-user',
            'services.fapshi.api_key' => 'test-api-key',
        ]);

        Http::fake([
            'https://sandbox.fapshi.com/initiate-pay' => Http::response([
                'message' => 'Invalid credentials',
            ], 401),
        ]);

        $response = $this->post(route('wallet.deposit'), [
            'amount' => 5000,
            'method' => 'mtn',
            'phone' => '670000000',
        ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_fapshi_webhook_rejects_invalid_signature()
    {
        $user = User::factory()->create([
            'balance' => 0,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 10000.00,
            'type' => 'deposit',
            'status' => 'pending',
            'reference' => 'DEP-WEBHOOK123',
            'payment_method' => 'orange',
            'payment_phone' => '694196055',
        ]);

        config(['services.fapshi.webhook_secret' => 'correct-secret']);

        $response = $this->postJson(route('webhook.fapshi'), [
            'transId' => 'trans-123',
            'externalId' => 'DEP-WEBHOOK123',
            'status' => 'SUCCESSFUL',
            'amount' => 10000,
        ], [
            'x-wh-secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401);

        $transaction->refresh();
        $user->refresh();

        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals(0, $user->balance);
    }

    public function test_withdrawal_approval_calls_fapshi_payout_api_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => -5000.00,
            'type' => 'withdrawal',
            'status' => 'pending',
            'reference' => 'WTH-TEST999',
            'payment_method' => 'orange',
            'payment_phone' => '699999999',
        ]);

        config([
            'services.fapshi.payout_api_user' => 'payout-api-user',
            'services.fapshi.api_key' => 'payout-api-key',
        ]);

        Http::fake([
            'https://sandbox.fapshi.com/payout' => Http::response([
                'message' => 'Accepted',
                'transId' => 'payout_123',
            ], 200),
        ]);

        $response = $this->post(route('admin.approve', ['id' => $transaction->id]));

        $response->assertSessionHas('success');
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_withdrawal_approval_handles_fapshi_payout_api_failure()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => -5000.00,
            'type' => 'withdrawal',
            'status' => 'pending',
            'reference' => 'WTH-TEST888',
            'payment_method' => 'mtn',
            'payment_phone' => '677777777',
        ]);

        config([
            'services.fapshi.payout_api_user' => 'payout-api-user',
            'services.fapshi.api_key' => 'payout-api-key',
        ]);

        Http::fake([
            'https://sandbox.fapshi.com/payout' => Http::response([
                'message' => 'Insufficient funds on your merchant account',
            ], 400),
        ]);

        $response = $this->post(route('admin.approve', ['id' => $transaction->id]));

        $response->assertSessionHasErrors('error');
        
        $transaction->refresh();
        $this->assertEquals('pending', $transaction->status);
    }

    public function test_check_status_returns_completed_status_when_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 5000,
            'type' => 'deposit',
            'status' => 'pending',
            'reference' => 'DEP-POLL123',
            'payment_method' => 'mtn',
            'payment_phone' => '670000000',
            'gateway_ref' => 'trans-123',
        ]);

        config([
            'services.fapshi.api_user' => 'test-api-user',
            'services.fapshi.api_key' => 'test-api-key',
        ]);

        Http::fake([
            'https://sandbox.fapshi.com/payment-status/trans-123' => Http::response([
                'status' => 'SUCCESSFUL',
            ], 200),
        ]);

        $response = $this->postJson(route('fapshi.check-status', ['reference' => $transaction->reference]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'completed',
        ]);

        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_check_status_mock_mode_automatically_completes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 5000,
            'type' => 'deposit',
            'status' => 'pending',
            'reference' => 'DEP-MOCK123',
            'payment_method' => 'mtn',
            'payment_phone' => '670000000',
            'gateway_ref' => 'mock-123',
        ]);

        config([
            'services.fapshi.api_user' => null,
            'services.fapshi.api_key' => null,
        ]);

        $response = $this->postJson(route('fapshi.check-status', ['reference' => $transaction->reference]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'completed',
        ]);

        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        
        $user->refresh();
        $this->assertEquals(5000, $user->balance);
    }
}
