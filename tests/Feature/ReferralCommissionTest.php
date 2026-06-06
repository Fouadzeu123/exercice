<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Node;
use App\Models\UserNode;
use App\Models\GenerationSession;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReferralCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_registration_generates_six_char_uppercase_alphanumeric_referral_code()
    {
        $referrer = User::factory()->create([
            'referral_code' => 'SPONS1',
        ]);

        $response = $this->post(route('register'), [
            'phone' => '237699999999',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'referral_code' => 'SPONS1',
        ]);

        $response->assertRedirect();
        
        $newUser = User::where('phone', '237699999999')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals($referrer->id, $newUser->referrer_id);
        
        $this->assertEquals(6, strlen($newUser->referral_code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $newUser->referral_code);
    }

    public function test_daily_referral_commission_distribution_rates()
    {
        // Hierarchy: userA -> userB -> userC -> userD
        $userA = User::factory()->create();
        $userB = User::factory()->create(['referrer_id' => $userA->id]);
        $userC = User::factory()->create(['referrer_id' => $userB->id]);
        $userD = User::factory()->create(['referrer_id' => $userC->id]);

        $userD->payDailyCommissions(1000.00);

        // Refresh users
        $userA->refresh();
        $userB->refresh();
        $userC->refresh();

        // Level 1 referrer (userC) should get 5% = 50.00 XAF
        $this->assertEquals(50.00, $userC->balance);
        $this->assertTrue(Transaction::where('user_id', $userC->id)->where('type', 'commission')->where('amount', 50.00)->exists());

        // Level 2 referrer (userB) should get 2% = 20.00 XAF
        $this->assertEquals(20.00, $userB->balance);
        $this->assertTrue(Transaction::where('user_id', $userB->id)->where('type', 'commission')->where('amount', 20.00)->exists());

        // Level 3 referrer (userA) should get 1% = 10.00 XAF
        $this->assertEquals(10.00, $userA->balance);
        $this->assertTrue(Transaction::where('user_id', $userA->id)->where('type', 'commission')->where('amount', 10.00)->exists());
    }

    public function test_node_claim_profit_pays_commissions()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['referrer_id' => $userA->id]);

        // Create node
        $node = Node::create([
            'name' => 'Test Node',
            'amount' => 5000,
            'duration' => 30,
            'generation_profit' => 100,
            'technology_level' => 1,
            'active' => true,
        ]);

        $userNode = UserNode::create([
            'user_id' => $userB->id,
            'node_id' => $node->id,
            'active' => true,
            'activated_at' => Carbon::now()->subHours(25),
            'expires_at' => Carbon::now()->addDays(29),
        ]);

        $session = GenerationSession::create([
            'user_id' => $userB->id,
            'user_node_id' => $userNode->id,
            'start_time' => Carbon::now()->subMinutes(5),
            'end_time' => Carbon::now()->subMinutes(3),
            'expected_profit' => 100,
            'status' => 'active',
        ]);

        $this->actingAs($userB);

        $response = $this->postJson("/nodes/claim-profit/{$session->id}");
        $response->assertOk();

        // userA (Level 1 sponsor of userB) should receive 5% commission of 100 = 5 XAF
        $userA->refresh();
        $this->assertEquals(5.00, $userA->balance);
        $this->assertTrue(Transaction::where('user_id', $userA->id)->where('type', 'commission')->where('amount', 5.00)->exists());
    }
}
