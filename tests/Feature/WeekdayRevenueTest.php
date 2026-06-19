<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Node;
use App\Models\UserNode;
use App\Models\VaultPlan;
use App\Models\VaultInvestment;
use App\Models\GenerationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WeekdayRevenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed some basic settings required for salary claims
        \App\Services\SettingsService::set('vip_salaries', [
            0 => 0.00,
            1 => 150.00,
            2 => 500.00,
            3 => 1500.00
        ]);
    }

    public function test_standard_node_rent_calculates_expiration_skipping_weekends()
    {
        // Set time to Friday June 19, 2026
        Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00'));

        $user = User::factory()->create([
            'balance' => 20000,
        ]);

        $node = Node::create([
            'name' => 'Neoverse Card',
            'amount' => 15000,
            'duration' => 30,
            'generation_profit' => 500,
            'technology_level' => 1,
            'active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('nodes.rent', $node->id));
        $response->assertRedirect();

        $userNode = UserNode::where('user_id', $user->id)->where('node_id', $node->id)->first();
        $this->assertNotNull($userNode);

        // Expiration should be exactly 30 weekdays from June 19, 2026, which is July 31, 2026
        $expectedExpiry = Carbon::parse('2026-07-31 12:00:00');
        $this->assertEquals($expectedExpiry->toDateTimeString(), $userNode->expires_at->toDateTimeString());

        Carbon::setTestNow(); // Reset faked date
    }

    public function test_standard_node_generation_start_blocked_on_weekends()
    {
        // 1. Setup faked date to Saturday June 20, 2026 (weekend)
        Carbon::setTestNow(Carbon::parse('2026-06-20 12:00:00'));

        $user = User::factory()->create();
        $node = Node::create([
            'name' => 'Neoverse Card',
            'amount' => 15000,
            'duration' => 30,
            'generation_profit' => 500,
            'technology_level' => 1,
            'active' => true,
        ]);

        $userNode = UserNode::create([
            'user_id' => $user->id,
            'node_id' => $node->id,
            'active' => true,
            'activated_at' => Carbon::now()->subDays(2),
            'expires_at' => Carbon::now()->addDays(28),
        ]);

        $this->actingAs($user);

        // Try to start generation on Saturday (should fail)
        $response = $this->postJson(route('generation.start'), [
            'user_node_id' => $userNode->id
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'La génération de revenus est disponible uniquement du lundi au vendredi.');

        // 2. Setup faked date to Monday June 22, 2026 (weekday)
        Carbon::setTestNow(Carbon::parse('2026-06-22 12:00:00'));

        // Update node activated_at so it passes the 24h delay check
        $userNode->update([
            'activated_at' => Carbon::now()->subHours(25)
        ]);

        $response = $this->postJson(route('generation.start'), [
            'user_node_id' => $userNode->id
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('generation_sessions', [
            'user_node_id' => $userNode->id,
            'status' => 'active',
        ]);

        Carbon::setTestNow();
    }

    public function test_vip_salary_claim_blocked_on_weekends()
    {
        $user = User::factory()->create([
            'vip_level' => 1,
            'balance' => 1000,
        ]);

        // Purchase a standard node to satisfy the first purchase constraint
        $node = Node::create([
            'name' => 'Neoverse Card',
            'amount' => 15000,
            'duration' => 30,
            'generation_profit' => 500,
            'technology_level' => 1,
            'active' => true,
        ]);

        $userNode = UserNode::create([
            'user_id' => $user->id,
            'node_id' => $node->id,
            'active' => true,
            'activated_at' => Carbon::parse('2026-06-10 12:00:00'),
            'expires_at' => Carbon::parse('2026-07-10 12:00:00'),
        ]);

        $this->actingAs($user);

        // 1. Try to claim on Sunday June 21, 2026 (should fail)
        Carbon::setTestNow(Carbon::parse('2026-06-21 12:00:00'));

        $response = $this->postJson(route('avip-products.claim-salary'));
        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Les réclamations de salaire journalier sont disponibles uniquement du lundi au vendredi.');

        // 2. Try to claim on Monday June 22, 2026 (should succeed)
        Carbon::setTestNow(Carbon::parse('2026-06-22 12:00:00'));

        $response = $this->postJson(route('avip-products.claim-salary'));
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('salary_amount', 150);

        Carbon::setTestNow();
    }

    public function test_vault_duration_and_payouts_unaffected_by_weekday_restrictions()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00')); // Friday

        $user = User::factory()->create([
            'balance' => 20000,
        ]);

        $vault = VaultPlan::create([
            'name' => 'Test Vault',
            'fixed_investment_amount' => 10000,
            'fixed_return' => 13000,
            'profit_amount' => 3000,
            'duration' => 30,
            'payout_type' => 'daily',
            'active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('vaults.invest', $vault->id));
        $response->assertRedirect();

        $investment = VaultInvestment::where('user_id', $user->id)->where('vault_plan_id', $vault->id)->first();
        $this->assertNotNull($investment);

        // Vault expiration must use addDays (calendar days). 30 days from June 19, 2026 is July 19, 2026
        $expectedExpiry = Carbon::parse('2026-07-19 12:00:00');
        $this->assertEquals($expectedExpiry->toDateTimeString(), $investment->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }
}
