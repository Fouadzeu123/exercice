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

        // Retrieve all active user nodes
        $activeUserNodes = UserNode::where('user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select('user_nodes.*', 'nodes.name as node_name', 'nodes.generation_profit', 'nodes.technology_level', 'nodes.amount as node_amount', 'nodes.duration as node_duration')
            ->orderBy('user_nodes.created_at', 'desc')
            ->get();

        $activeNodes = $activeUserNodes->map(function ($userNode) {
            // Check if there is an active session
            $session = GenerationSession::where('user_node_id', $userNode->id)
                ->where('status', 'active')
                ->first();
                
            $sessionData = null;
            $status = 'ready'; // ready, running, claimable, cooldown
            $cooldownSeconds = 0;
            $cooldownExpiresAt = null;

            if ($session) {
                $endTime = Carbon::parse($session->end_time);
                if ($endTime->isFuture()) {
                    $status = 'running';
                    $sessionData = [
                        'id' => $session->id,
                        'start_time' => $session->start_time,
                        'end_time' => $session->end_time,
                        'expected_profit' => (float)$session->expected_profit,
                        'remaining_seconds' => max(0, Carbon::now()->diffInSeconds($endTime, false)),
                    ];
                } else {
                    $status = 'claimable';
                    $sessionData = [
                        'id' => $session->id,
                        'start_time' => $session->start_time,
                        'end_time' => $session->end_time,
                        'expected_profit' => (float)$session->expected_profit,
                        'remaining_seconds' => 0,
                    ];
                }
            } else {
                // Check 24h purchase delay
                $activationDelay = Carbon::parse($userNode->activated_at)->addHours(24);
                if ($activationDelay->isFuture()) {
                    $status = 'cooldown';
                    $cooldownExpiresAt = $activationDelay->toIso8601String();
                    $cooldownSeconds = max(0, Carbon::now()->diffInSeconds($activationDelay, false));
                } else {
                    // Check subsequent 24h rate limit
                    $lastSession = GenerationSession::where('user_node_id', $userNode->id)
                        ->orderBy('start_time', 'desc')
                        ->first();

                    if ($lastSession) {
                        $sessionDelay = Carbon::parse($lastSession->start_time)->addHours(24);
                        if ($sessionDelay->isFuture()) {
                            $status = 'cooldown';
                            $cooldownExpiresAt = $sessionDelay->toIso8601String();
                            $cooldownSeconds = max(0, Carbon::now()->diffInSeconds($sessionDelay, false));
                        }
                    }
                }
            }

            return [
                'id' => $userNode->id,
                'node_id' => $userNode->node_id,
                'node_name' => $userNode->node_name,
                'generation_profit' => (float)$userNode->generation_profit,
                'node_amount' => (float)$userNode->node_amount,
                'technology_level' => $userNode->technology_level,
                'activated_at' => $userNode->activated_at,
                'expires_at' => $userNode->expires_at,
                'status' => $status,
                'session' => $sessionData,
                'cooldown_seconds' => $cooldownSeconds,
                'cooldown_expires_at' => $cooldownExpiresAt,
            ];
        });

        return Inertia::render('Generate', [
            'activeNodes' => $activeNodes,
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

        // Enforce required active referrals constraint
        if ($node->required_active_referrals > 0) {
            $activeReferralsCount = \App\Models\User::where('referrer_id', $user->id)
                ->where(function($query) {
                    $query->whereHas('userNodes')
                          ->orWhereHas('userAVIPProducts');
                })
                ->count();
            if ($activeReferralsCount < $node->required_active_referrals) {
                return back()->withErrors(['error' => "Ce produit requiert au moins {$node->required_active_referrals} filleul(s) actif(s) ayant loué/acheté un produit pour être déverrouillé. Vous en avez actuellement : {$activeReferralsCount}."]);
            }
        }

        // Enforce limited offer stock limit
        if ($node->stock_quantity !== null) {
            $rentedCount = UserNode::where('node_id', $node->id)
                ->where('active', true)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', Carbon::now());
                })
                ->count();
            if ($rentedCount >= $node->stock_quantity) {
                return back()->withErrors(['error' => 'Cette offre est épuisée (rupture de stock).']);
            }
        }

        // Enforce limited offer quota per account
        if ($node->limited_purchase_count !== null) {
            $userRentedCount = UserNode::where('user_id', $user->id)
                ->where('node_id', $node->id)
                ->where('active', true)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', Carbon::now());
                })
                ->count();
            if ($userRentedCount >= $node->limited_purchase_count) {
                return back()->withErrors(['error' => "Vous avez atteint le quota maximal autorisé pour ce produit ({$node->limited_purchase_count} par compte)."]);
            }
        }

        // Database transaction to guarantee consistency
        try {
            DB::transaction(function () use ($user, $node) {
                // Check if the user has already rented this node in the past
                $alreadyRented = UserNode::where('user_id', $user->id)
                    ->where('node_id', $node->id)
                    ->exists();

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
                    'expires_at' => Carbon::now()->addWeekdays($node->duration),
                ]);

                // Log the purchase transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => -$node->amount,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'reference' => 'PUR-' . strtoupper(bin2hex(random_bytes(4))),
                ]);

                // Pay referral commission if the user has a referrer and hasn't rented this specific node before
                if ($user->referrer_id && !$alreadyRented) {
                    $sponsor = \App\Models\User::find($user->referrer_id);
                    if ($sponsor) {
                        $commissionAmount = (float)($node->referral_reward ?? 0.00);

                        if ($commissionAmount > 0) {
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

    public function startGeneration(Request $request)
    {
        if (Carbon::now()->isWeekend()) {
            return response()->json(['error' => 'La génération de revenus est disponible uniquement du lundi au vendredi.'], 422);
        }

        $request->validate([
            'user_node_id' => 'required|integer|exists:user_nodes,id'
        ]);

        $user = Auth::user();
        $userNodeId = $request->user_node_id;

        // 1. Verify user owns this active node
        $activeUserNode = UserNode::where('user_nodes.id', $userNodeId)
            ->where('user_nodes.user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('user_nodes.expires_at')
                      ->orWhere('user_nodes.expires_at', '>', Carbon::now());
            })
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select('user_nodes.*', 'nodes.generation_profit', 'nodes.name as node_name')
            ->first();

        if (!$activeUserNode) {
            return response()->json(['error' => 'Vous devez disposer d\'un nœud actif pour démarrer la génération.'], 422);
        }

        // Enforce first generation only 24 hours after node purchase/activation
        if ($activeUserNode->activated_at && Carbon::parse($activeUserNode->activated_at)->addHours(24)->isFuture()) {
            $availableTime = Carbon::parse($activeUserNode->activated_at)->addHours(24);
            return response()->json(['error' => "Vous pourrez démarrer votre première session de co-traitement et générer des revenus 24 heures après l'achat de votre nœud. Disponible le : " . $availableTime->format('d/m/Y H:i:s')], 422);
        }

        // Enforce subsequent generations only 24 hours after the last started generation session for this specific node
        $lastSession = GenerationSession::where('user_node_id', $userNodeId)
            ->orderBy('start_time', 'desc')
            ->first();

        if ($lastSession && Carbon::parse($lastSession->start_time)->addHours(24)->isFuture()) {
            $availableTime = Carbon::parse($lastSession->start_time)->addHours(24);
            return response()->json(['error' => "Vous ne pouvez générer des revenus qu'une seule fois toutes les 24 heures sur ce nœud. Prochaine session disponible à partir de : " . $availableTime->format('d/m/Y H:i:s')], 422);
        }

        // 2. Check if a session is already running for this specific node
        $runningSession = GenerationSession::where('user_node_id', $userNodeId)
            ->where('status', 'active')
            ->where('end_time', '>', Carbon::now())
            ->first();

        if ($runningSession) {
            return response()->json(['error' => 'Une session de génération est déjà en cours sur ce nœud.'], 422);
        }

        // 3. Start a new 2-minute session
        $startTime = Carbon::now();
        $endTime = Carbon::now()->addMinutes(2);

        $session = GenerationSession::create([
            'user_id' => $user->id,
            'user_node_id' => $userNodeId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'expected_profit' => $activeUserNode->generation_profit,
            'status' => 'active',
        ]);

        return response()->json([
            'id' => $session->id,
            'user_node_id' => $userNodeId,
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

                // Distribute daily referral commissions
                $user->payDailyCommissions($session->expected_profit);
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
