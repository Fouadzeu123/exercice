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

        // Level 2 referrer (userB) should get 3% = 30.00 XAF
        $this->assertEquals(30.00, $userB->balance);
        $this->assertTrue(Transaction::where('user_id', $userB->id)->where('type', 'commission')->where('amount', 30.00)->exists());

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

        $response = $this->postJson("/generation/{$session->id}/claim");
        $response->assertOk();

        // userA (Level 1 sponsor of userB) should receive 5% commission of 100 = 5 XAF
        $userA->refresh();
        $this->assertEquals(5.00, $userA->balance);
        $this->assertTrue(Transaction::where('user_id', $userA->id)->where('type', 'commission')->where('amount', 5.00)->exists());
    }

    public function test_vault_daily_payout_and_commissions()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['referrer_id' => $userA->id]);

        $vaultPlan = \App\Models\VaultPlan::create([
            'name' => 'Daily Vault',
            'fixed_investment_amount' => 10000,
            'fixed_return' => 12000,
            'profit_amount' => 2000,
            'duration' => 10,
            'payout_type' => 'daily',
            'active' => true,
        ]);

        // Create vault investment created 2 days ago (so 2 daily payouts are due)
        $investment = \App\Models\VaultInvestment::create([
            'user_id' => $userB->id,
            'vault_plan_id' => $vaultPlan->id,
            'amount' => 10000,
            'return_amount' => 12000,
            'expires_at' => Carbon::now()->addDays(8),
            'status' => 'active',
            'created_at' => Carbon::now()->subHours(49), // slightly more than 2 days
        ]);

        // Process payouts
        \App\Models\VaultInvestment::processUserPayouts($userB);

        $userB->refresh();
        $userA->refresh();
        $investment->refresh();

        // 2 days of daily payouts = (12000 / 10) * 2 = 2400 XAF
        $this->assertEquals(2400.00, $userB->balance);
        $this->assertEquals(2, $investment->payouts_claimed);

        // Sponsor userA should receive 5% daily commission on 2400 XAF = 120 XAF
        $this->assertEquals(120.00, $userA->balance);
        $this->assertTrue(Transaction::where('user_id', $userA->id)->where('type', 'commission')->where('amount', 120.00)->exists());
    }

    public function test_vault_expiration_payout()
    {
        $user = User::factory()->create();

        $vaultPlan = \App\Models\VaultPlan::create([
            'name' => 'Expiry Vault',
            'fixed_investment_amount' => 10000,
            'fixed_return' => 15000,
            'profit_amount' => 5000,
            'duration' => 10,
            'payout_type' => 'on_expiration',
            'active' => true,
        ]);

        // Create active vault investment that has expired
        $investment = \App\Models\VaultInvestment::create([
            'user_id' => $user->id,
            'vault_plan_id' => $vaultPlan->id,
            'amount' => 10000,
            'return_amount' => 15000,
            'expires_at' => Carbon::now()->subHours(2), // expired 2 hours ago
            'status' => 'active',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Process payouts
        \App\Models\VaultInvestment::processUserPayouts($user);

        $user->refresh();
        $investment->refresh();

        // Should receive full return of 15000 XAF at once
        $this->assertEquals(15000.00, $user->balance);
        $this->assertEquals('completed', $investment->status);
    }

    public function test_team_controller_and_dashboard_controller_sum_all_commissions_and_detect_active_referrals_properly()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['referrer_id' => $userA->id]);

        // 1. Create check-in commission (type 'commission')
        Transaction::create([
            'user_id' => $userA->id,
            'amount' => 50.00,
            'type' => 'commission',
            'status' => 'completed',
            'reference' => 'COM-L1-AABBCC',
        ]);

        // 2. Create node direct commission (type 'commission')
        Transaction::create([
            'user_id' => $userA->id,
            'amount' => 500.00,
            'type' => 'commission',
            'status' => 'completed',
            'reference' => 'COM-DDEEFF',
        ]);

        // 3. Create node active for userB (referree)
        $node = Node::create([
            'name' => 'Active Node',
            'amount' => 5000,
            'duration' => 30,
            'generation_profit' => 100,
            'technology_level' => 1,
            'active' => true,
        ]);
        UserNode::create([
            'user_id' => $userB->id,
            'node_id' => $node->id,
            'active' => true,
            'activated_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $this->actingAs($userA);

        // Access Dashboard Gains
        $response1 = $this->get('/gains');
        $response1->assertOk();
        
        // Assert that the dashboard sum of parrainage commissions equals 550.00
        $this->assertEquals(550.00, $response1->viewData('page')['props']['referralCommissions']);

        // Access Team Page
        $response2 = $this->get('/team');
        $response2->assertOk();

        // Assert that total parrainage commissions on team page equals 550.00
        $this->assertEquals(550.00, $response2->viewData('page')['props']['stats']['total_commissions']);

        // Assert that userB is counted as active member
        $this->assertEquals(1, $response2->viewData('page')['props']['stats']['active_members']);
    }

    public function test_retroactive_referral_rewards_artisan_command()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['referrer_id' => $userA->id]);

        // Create node with referral reward
        $node = Node::create([
            'name' => 'Reward Node',
            'amount' => 5000,
            'duration' => 30,
            'generation_profit' => 100,
            'technology_level' => 1,
            'referral_reward' => 1200,
            'active' => true,
        ]);

        // Create rental for userB (filleul) WITHOUT creating a commission for userA (parrain)
        $userNode = UserNode::create([
            'user_id' => $userB->id,
            'node_id' => $node->id,
            'active' => true,
            'activated_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Assert userA balance is 0 and no commission exists
        $this->assertEquals(0, $userA->balance);

        // Run parrainage:retro-rewards command
        $this->artisan('parrainage:retro-rewards')
             ->expectsOutputToContain('Récompense manquante détectée')
             ->expectsOutputToContain('Total récompenses attribuées : 1')
             ->expectsOutputToContain('Montant total distribué : 1200')
             ->assertExitCode(0);

        // Check that userA balance is credited
        $userA->refresh();
        $this->assertEquals(1200.00, $userA->balance);

        // Check that unique transaction is logged
        $this->assertTrue(Transaction::where('user_id', $userA->id)
            ->where('reference', 'COM-RET-N-' . $userB->id . '-' . $userNode->id)
            ->where('amount', 1200.00)
            ->exists());

        // Run command again, should not reward twice
        $this->artisan('parrainage:retro-rewards')
             ->expectsOutputToContain('Total récompenses attribuées : 0')
             ->assertExitCode(0);
    }
}

