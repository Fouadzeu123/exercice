<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\UserNode;
use App\Models\GenerationSession;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class NodeController extends Controller
{
    /**
     * Display a listing of infrastructure nodes for rent.
     */
    public function index()
    {
        $user = Auth::user();
        $nodes = Node::where('active', true)->orderBy('technology_level', 'asc')->get();

        // Retrieve current active user node
        $activeUserNode = UserNode::where('user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->first();

        return Inertia::render('NodesMarket', [
            'nodes' => $nodes,
            'activeUserNode' => $activeUserNode,
        ]);
    }

    /**
     * Render the dedicated high-tech real-time generation page.
     */
    public function generatePage()
    {
        $user = Auth::user();

        // Retrieve current active user node
        $activeUserNode = UserNode::where('user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select('user_nodes.*', 'nodes.name as node_name', 'nodes.generation_profit', 'nodes.technology_level', 'nodes.amount as node_amount')
            ->first();

        // Retrieve active running session
        $activeSession = GenerationSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_time', '>', Carbon::now())
            ->first();

        return Inertia::render('Generate', [
            'activeUserNode' => $activeUserNode,
            'activeSession' => $activeSession ? [
                'id' => $activeSession->id,
                'start_time' => $activeSession->start_time,
                'end_time' => $activeSession->end_time,
                'expected_profit' => $activeSession->expected_profit,
                'remaining_seconds' => max(0, Carbon::now()->diffInSeconds(Carbon::parse($activeSession->end_time), false)),
            ] : null,
        ]);
    }

    /**
     * Handle the rental or upgrade of an infrastructure node.
     */
    public function rent(Request $request, $id)
    {
        $user = Auth::user();
        $node = Node::findOrFail($id);

        if (!$node->active) {
            return back()->withErrors(['error' => 'Ce nœud n\'est pas disponible.']);
        }

        // Retrieve user's current active node (if any)
        $currentActive = UserNode::where('user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select('user_nodes.*', 'nodes.technology_level', 'nodes.amount as node_amount', 'nodes.name as node_name')
            ->first();

        // Database transaction to guarantee consistency
        try {
            DB::transaction(function () use ($user, $node, $currentActive) {
                $refundAmount = 0;

                if ($currentActive) {
                    // Upgrade Rule: new node technology_level must be strictly greater than current
                    if ($node->technology_level <= $currentActive->technology_level) {
                        throw new \Exception('Vous ne pouvez louer qu\'un nœud de niveau technologique supérieur à votre nœud actuel.');
                    }

                    // Upgrading automatically refunds the previous node investment!
                    $refundAmount = $currentActive->node_amount;
                    
                    // Mark current active node as inactive
                    UserNode::where('id', $currentActive->id)->update([
                        'active' => false,
                        'expires_at' => Carbon::now()
                    ]);

                    // Add refund to user balance
                    $user->balance += $refundAmount;

                    // Log the refund transaction
                    Transaction::create([
                        'user_id' => $user->id,
                        'amount' => $refundAmount,
                        'type' => 'refund',
                        'status' => 'completed',
                        'reference' => 'REF-' . strtoupper(bin2hex(random_bytes(4))),
                    ]);
                }

                // Check if user has sufficient balance for the new rental
                if ($user->balance < $node->amount) {
                    throw new \Exception('Solde insuffisant pour louer ce nœud d\'infrastructure.');
                }

                // Deduct the cost from the user's balance
                $user->balance -= $node->amount;
                $user->save();

                // Create the user node link
                UserNode::create([
                    'user_id' => $user->id,
                    'node_id' => $node->id,
                    'active' => true,
                    'activated_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addDays($node->duration),
                ]);

                // Log the purchase transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => -$node->amount,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'reference' => 'PUR-' . strtoupper(bin2hex(random_bytes(4))),
                ]);

                // Pay referral commission if the user has a referrer
                if ($user->referrer_id) {
                    $sponsor = \App\Models\User::find($user->referrer_id);
                    if ($sponsor) {
                        $sponsorVip = $sponsor->vip_level ?? 1;
                        $commissionRate = 0.05; // VIP 1 default
                        if ($sponsorVip == 2) {
                            $commissionRate = 0.07;
                        } elseif ($sponsorVip == 3) {
                            $commissionRate = 0.10;
                        } elseif ($sponsorVip == 4) {
                            $commissionRate = 0.13;
                        } elseif ($sponsorVip >= 5) {
                            $commissionRate = 0.17;
                        }

                        $commissionAmount = $node->amount * $commissionRate;

                        // Credit sponsor balance
                        $sponsor->balance += $commissionAmount;
                        $sponsor->save();

                        // Log commission transaction
                        Transaction::create([
                            'user_id' => $sponsor->id,
                            'amount' => $commissionAmount,
                            'type' => 'earnings',
                            'status' => 'completed',
                            'reference' => 'COM-' . strtoupper(bin2hex(random_bytes(4))),
                        ]);
                    }
                }

                // Recalculate status for user (their personal investment changed)
                $user->recalculateVipAndAvipStatus();

                // Recalculate status for sponsor (their team volume & active referrals changed)
                if (isset($sponsor)) {
                    $sponsor->recalculateVipAndAvipStatus();
                }
            });

            return redirect()->back()->with('success', 'Nœud loué avec succès.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Start the 2-minute real-time generation session.
     */
    public function startGeneration(Request $request)
    {
        $user = Auth::user();

        // 1. Verify user has an active node
        $activeUserNode = UserNode::where('user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select('user_nodes.*', 'nodes.generation_profit', 'nodes.name as node_name')
            ->first();

        if (!$activeUserNode) {
            return response()->json(['error' => 'Vous devez disposer d\'un nœud actif pour démarrer la génération.'], 422);
        }

        // 2. Check if a session is already running
        $runningSession = GenerationSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_time', '>', Carbon::now())
            ->first();

        if ($runningSession) {
            return response()->json(['error' => 'Une session de génération est déjà en cours.'], 422);
        }

        // 3. Start a new 2-minute session
        $startTime = Carbon::now();
        $endTime = Carbon::now()->addMinutes(2);

        $session = GenerationSession::create([
            'user_id' => $user->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'expected_profit' => $activeUserNode->generation_profit,
            'status' => 'active',
        ]);

        return response()->json([
            'id' => $session->id,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'expected_profit' => $session->expected_profit,
            'remaining_seconds' => 120,
        ]);
    }

    /**
     * Claim profits from a completed generation session.
     */
    public function claimProfit(Request $request, $id)
    {
        $user = Auth::user();
        $session = GenerationSession::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if ($session->status !== 'active') {
            return response()->json(['error' => 'Cette session a déjà été réclamée ou est expirée.'], 422);
        }

        // Secure server-side time verification with a 10-second grace period for client clock desync
        if (Carbon::now()->timestamp + 10 < Carbon::parse($session->end_time)->timestamp) {
            return response()->json(['error' => 'La session de génération est toujours en cours.'], 422);
        }

        try {
            DB::transaction(function () use ($user, $session) {
                // Update session state
                $session->update(['status' => 'claimed']);

                // Add rewards to user balance
                $user->balance += $session->expected_profit;
                $user->save();

                // Create transaction history
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $session->expected_profit,
                    'type' => 'earnings',
                    'status' => 'completed',
                    'reference' => 'GEN-' . strtoupper(bin2hex(random_bytes(4))),
                ]);
            });

            return response()->json([
                'success' => true,
                'profit' => $session->expected_profit,
                'new_balance' => $user->balance
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la réclamation des gains : ' . $e->getMessage()], 500);
        }
    }
}
