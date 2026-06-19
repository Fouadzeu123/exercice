<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Services\NotchPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotchPayTest extends TestCase
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
            'services.notchpay.public_key' => 'pk_test_public_key',
            'services.notchpay.secret_key' => 'sb_test_secret_key',
        ]);

        $this->mock(NotchPayService::class, function ($mock) {
            $mock->shouldReceive('initializePayment')->once()->andReturn((object)[
                'transaction' => (object)['reference' => 'pay-123']
            ]);
        });

        $response = $this->post(route('wallet.deposit'), [
            'amount' => 5000,
            'method' => 'mtn',
            'phone' => '670000000',
        ]);

        $transaction = Transaction::where('user_id', $user->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('pay-123', $transaction->gateway_ref);

        $response->assertRedirect(route('notchpay.pay', ['reference' => $transaction->reference]));
    }

    public function test_charge_payment_success()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 5000,
            'type' => 'deposit',
            'status' => 'pending',
            'reference' => 'DEP-CHARGE123',
            'payment_method' => 'mtn',
            'payment_phone' => '670000000',
            'gateway_ref' => 'pay-123',
        ]);

        config([
            'services.notchpay.public_key' => 'pk_test_public_key',
            'services.notchpay.secret_key' => 'sb_test_secret_key',
        ]);

        $this->mock(NotchPayService::class, function ($mock) {
            $mock->shouldReceive('chargePayment')->with('pay-123', [
                'channel' => 'cm.orange',
                'data' => [
                    'phone' => '+237694196055',
                ],
            ])->once()->andReturn((object)[]);
        });

        $response = $this->postJson(route('notchpay.charge', ['reference' => $transaction->reference]), [
            'method' => 'orange',
            'phone' => '694196055',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Notification de paiement envoyée.',
        ]);

        $transaction->refresh();
        $this->assertEquals('orange', $transaction->payment_method);
        $this->assertEquals('694196055', $transaction->payment_phone);
    }

    public function test_charge_payment_validation_fails()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 5000,
            'type' => 'deposit',
            'status' => 'pending',
            'reference' => 'DEP-CHARGE456',
            'payment_method' => 'mtn',
            'payment_phone' => '670000000',
            'gateway_ref' => 'pay-123',
        ]);

        $response = $this->postJson(route('notchpay.charge', ['reference' => $transaction->reference]), [
            'method' => 'invalid-operator',
            'phone' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['method', 'phone']);
    }

    public function test_notchpay_webhook_verifies_signature_and_completes_payment()
    {
        $user = User::factory()->create([
            'balance' => 0,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 10000.00,
            'type' => 'deposit',
            'status' => 'pending',
            'reference' => 'DEP-NOTCH123',
            'payment_method' => 'mtn',
            'payment_phone' => '670000000',
        ]);

        $secret = 'notch-webhook-secret';
        config(['services.notchpay.webhook_secret' => $secret]);

        $payload = [
            'type' => 'payment.complete',
            'data' => [
                'reference' => 'DEP-NOTCH123',
                'status' => 'complete',
                'amount' => 10000,
            ]
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $secret);

        $response = $this->postJson(route('webhook.notchpay'), $payload, [
            'x-notch-signature' => $signature,
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
            'services.notchpay.public_key' => null,
            'services.notchpay.secret_key' => null,
        ]);

        $response = $this->post(route('wallet.deposit'), [
            'amount' => 5000,
            'method' => 'mtn',
            'phone' => '670000000',
        ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_notchpay_webhook_rejects_invalid_signature()
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

        config(['services.notchpay.webhook_secret' => 'correct-secret']);

        $payload = [
            'type' => 'payment.complete',
            'data' => [
                'reference' => 'DEP-WEBHOOK123',
                'status' => 'complete',
                'amount' => 10000,
            ]
        ];

        $response = $this->postJson(route('webhook.notchpay'), $payload, [
            'x-notch-signature' => 'wrong-signature',
        ]);

        $response->assertStatus(401);

        $transaction->refresh();
        $user->refresh();

        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals(0, $user->balance);
    }

    public function test_withdrawal_approval_calls_notchpay_transfer_api_successfully()
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
            'services.notchpay.public_key' => 'pk_test_public_key',
            'services.notchpay.secret_key' => 'sb_test_secret_key',
        ]);

        $this->mock(NotchPayService::class, function ($mock) {
            $mock->shouldReceive('createBeneficiary')->once()->andReturn((object)[
                'id' => 'ben-123'
            ]);
            $mock->shouldReceive('initializeTransfer')->once()->andReturn((object)[]);
        });

        $response = $this->post(route('admin.approve', ['id' => $transaction->id]));

        $response->assertSessionHas('success');
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
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
            'gateway_ref' => 'pay-123',
        ]);

        config([
            'services.notchpay.public_key' => 'pk_test_public_key',
            'services.notchpay.secret_key' => 'sb_test_secret_key',
        ]);

        $this->mock(NotchPayService::class, function ($mock) {
            $mock->shouldReceive('verifyPayment')->with('pay-123')->once()->andReturn((object)[
                'status' => 'complete'
            ]);
        });

        $response = $this->postJson(route('notchpay.check-status', ['reference' => $transaction->reference]));

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
            'services.notchpay.public_key' => null,
            'services.notchpay.secret_key' => null,
        ]);

        $response = $this->postJson(route('notchpay.check-status', ['reference' => $transaction->reference]));

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
