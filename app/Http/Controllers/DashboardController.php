<?php

namespace App\Http\Controllers;

use App\Models\UserNode;
use App\Models\GenerationSession;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Automatically process vault payouts for the authenticated user
        if ($user) {
            \App\Models\VaultInvestment::processUserPayouts($user);
        }

        // Retrieve the active user nodes to calculate aggregate values
        $activeUserNodes = UserNode::where('user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select('nodes.generation_profit')
            ->get();

        $activeUserNodesCount = $activeUserNodes->count();

        // Retrieve recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate statistics
        $totalGenerated = GenerationSession::where('user_id', $user->id)
            ->where('status', 'claimed')
            ->sum('expected_profit');

        $dailyProfitRate = (float) $activeUserNodes->sum('generation_profit');

        // Retrieve active announcements
        $announcements = \App\Models\Announcement::where('active', true)->orderBy('created_at', 'desc')->get();

        // Retrieve all available nodes for visual rendering direct rent
        $nodes = \App\Models\Node::orderBy('technology_level', 'asc')->get();

        // Retrieve active vault plans to show in combined marketplace
        $vaultPlans = \App\Models\VaultPlan::where('active', true)->get();

        // Retrieve active AVIP products to show in AVIP tab
        $avipProducts = \App\Models\AVIPProduct::where('active', true)->orderBy('avip_level', 'asc')->get();

        return Inertia::render('Dashboard', [
            'activeUserNodesCount' => $activeUserNodesCount,
            'recentTransactions' => $recentTransactions,
            'announcements' => $announcements,
            'nodes' => $nodes,
            'vaultPlans' => $vaultPlans,
            'avipProducts' => $avipProducts,
            'stats' => [
                'total_generated' => (float) $totalGenerated,
                'daily_profit_rate' => (float) $dailyProfitRate,
                'vip_level' => $user->vip_level ?? 0,
                'avip_level' => $user->avip_level ?? 0,
            ]
        ]);
    }

    /**
     * Effectue la synchronisation quotidienne du nœud principal (Pointage).
     */    public function checkin()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Référence unique par jour et par utilisateur : CKI-USER_ID-YYYYMMDD
        $reference = 'CKI-' . $user->id . '-' . $today->format('Ymd');

        try {
            \DB::transaction(function () use ($user, $reference) {
                // Lock user record immediately
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->firstOrFail();

                // Re-verify checking inside locked transaction to prevent race conditions
                $alreadyCheckedIn = Transaction::where('user_id', $lockedUser->id)
                    ->where('type', 'earnings')
                    ->where('reference', $reference)
                    ->exists();

                if ($alreadyCheckedIn) {
                    throw new \InvalidArgumentException('Votre console principale a déjà été synchronisée pour aujourd\'hui. Veuillez revenir demain.');
                }

                // Montant du bonus quotidien : 77 FCFA
                $bonusAmount = 77.00;

                // Ajouter au solde
                $lockedUser->balance += $bonusAmount;
                $lockedUser->save();

                // Créer la transaction de pointage
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'amount' => $bonusAmount,
                    'type' => 'earnings',
                    'status' => 'completed',
                    'reference' => $reference,
                ]);
            });

            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Synchronisation du nœud principal réussie. +77 FCFA injectés.',
                'new_balance' => $user->balance
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Échec de la synchronisation : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Page Commandes — Récapitulatif des produits/serveurs achetés
     */
    public function commandes()
    {
        $user = Auth::user();

        $userNodes = UserNode::where('user_id', $user->id)
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select(
                'user_nodes.*',
                'nodes.name as node_name',
                'nodes.amount as node_amount',
                'nodes.generation_profit',
                'nodes.duration',
                'nodes.technology_level',
                'nodes.image as image_url'
            )
            ->orderBy('user_nodes.created_at', 'desc')
            ->get();

        $vaultInvestments = \App\Models\VaultInvestment::where('user_id', $user->id)
            ->with('vaultPlan')
            ->orderBy('created_at', 'desc')
            ->get();

        $userAvips = \App\Models\UserAVIPProduct::where('user_id', $user->id)
            ->with('avipProduct')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Commandes', [
            'orders' => $userNodes,
            'vaultInvestments' => $vaultInvestments,
            'userAvips' => $userAvips,
        ]);
    }



    /**
     * Page Gains — Informations détaillées sur tous les gains
     */
    public function gains()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Gains du jour
        $todayEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['claim', 'commission', 'earnings'])
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('amount');

        // Gains de la semaine
        $weekEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['claim', 'commission', 'earnings'])
            ->where('status', 'completed')
            ->where('created_at', '>=', $today->copy()->startOfWeek())
            ->sum('amount');

        // Total des gains
        $totalEarnings = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['claim', 'commission', 'earnings'])
            ->where('status', 'completed')
            ->sum('amount');

        // Commissions de parrainage
        $referralCommissions = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['commission', 'earnings'])
            ->where('reference', 'like', 'COM-%')
            ->where('status', 'completed')
            ->sum('amount');

        // Gains de génération
        $generationEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'claim')
            ->where('status', 'completed')
            ->sum('amount');

        // Historique détaillé des gains
        $earningsHistory = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['claim', 'commission', 'earnings'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        return Inertia::render('Gains', [
            'todayEarnings' => (float) $todayEarnings,
            'weekEarnings' => (float) $weekEarnings,
            'totalEarnings' => (float) $totalEarnings,
            'referralCommissions' => (float) $referralCommissions,
            'generationEarnings' => (float) $generationEarnings,
            'earningsHistory' => $earningsHistory,
        ]);
    }

    /**
     * Affiche les détails d'un produit (GPU Node ou Vault Plan) pour investissement.
     */
    public function showProduct($type, $id)
    {
        $user = Auth::user();

        // Récupérer le nœud actif actuel de l'utilisateur
        $activeUserNode = UserNode::where('user_id', $user->id)
            ->where('user_nodes.active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->join('nodes', 'user_nodes.node_id', '=', 'nodes.id')
            ->select('user_nodes.*', 'nodes.name as node_name', 'nodes.generation_profit', 'nodes.technology_level', 'nodes.amount as node_amount')
            ->first();

        $product = null;
        if ($type === 'node') {
            $product = \App\Models\Node::findOrFail($id);
            $product->isVault = false;
            $product->isAvip = false;
        } elseif ($type === 'vault') {
            $product = \App\Models\VaultPlan::findOrFail($id);
            $product->isVault = true;
            $product->isAvip = false;
            // Assurer la compatibilité avec les clés de Node
            $product->amount = (float) $product->fixed_investment_amount;
            $product->generation_profit = (float) $product->profit_amount / $product->duration;
        } elseif ($type === 'avip') {
            $product = \App\Models\AVIPProduct::findOrFail($id);
            $product->isVault = false;
            $product->isAvip = true;
            $product->duration = $product->duration ?? 7;
            $product->generation_profit = (float) $product->daily_salary;
        } else {
            abort(404, 'Type de produit inconnu');
        }

        $activeReferralsCount = \App\Models\User::where('referrer_id', $user->id)
            ->hasActiveInvestments()
            ->count();

        return Inertia::render('ProductDetails', [
            'product' => $product,
            'type' => $type,
            'activeUserNode' => $activeUserNode,
            'activeReferralsCount' => $activeReferralsCount,
        ]);
    }

    /**
     * Page de Tirage au sort (Roue de la Fortune).
     */
    public function tiragePage()
    {
        $user = Auth::user();
        
        $myWinnings = Transaction::where('user_id', $user->id)
            ->where('type', 'earnings')
            ->where('reference', 'like', 'DRAW-%')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($tx) {
                return [
                    'id' => $tx->id,
                    'amount' => (float) $tx->amount,
                    'reference' => $tx->reference,
                    'created_at' => $tx->created_at->toIso8601String()
                ];
            });

        return Inertia::render('Tirage', [
            'userBalance' => (float) $user->balance,
            'drawSpins' => (int) $user->draw_spins,
            'myWinnings' => $myWinnings
        ]);
    }

    /**
     * Traitement du tirage au sort (Lancer de la Roue).
     */
    public function spinWheel()
    {
        $user = Auth::user();

        try {
            return \DB::transaction(function () use ($user) {
                // Lock user record inside transaction
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->firstOrFail();

                if ($lockedUser->draw_spins < 1) {
                    throw new \InvalidArgumentException("Vous n'avez plus de lancers disponibles. Les lancers sont attribués par le service RH.");
                }

                // Déduire le lancer
                $lockedUser->draw_spins -= 1;

                // Définir les gains et leurs probabilités cumulées (Total = 1000)
                $prizes = [777, 1777, 7777, 77777, 177777, 777777, 1777777];
                
                $winnerIndex = null;
                
                if ($lockedUser->next_spin_prize_index !== null && $lockedUser->next_spin_prize_index >= 0 && $lockedUser->next_spin_prize_index <= 6) {
                    $winnerIndex = $lockedUser->next_spin_prize_index;
                    // Reset rigging immediately
                    $lockedUser->next_spin_prize_index = null;
                } else {
                    $winnerIndex = 0; // Default always to 777
                }

                $wonAmount = (float) $prizes[$winnerIndex];

                // Ajouter le gain
                $lockedUser->balance += $wonAmount;
                $lockedUser->save();

                // Créer l'historique de transaction
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'amount' => $wonAmount,
                    'type' => 'earnings',
                    'status' => 'completed',
                    'reference' => 'DRAW-' . strtoupper(bin2hex(random_bytes(4))),
                ]);

                return response()->json([
                    'success' => true,
                    'won_amount' => $wonAmount,
                    'winner_index' => $winnerIndex,
                    'new_balance' => $lockedUser->balance,
                    'draw_spins' => $lockedUser->draw_spins,
                    'message' => "Félicitations ! Vous avez gagné {$wonAmount} XAF !"
                ]);
            });

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du traitement du tirage : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Page Presentation — Informations sur l'entreprise et contrat
     */
    public function presentation()
    {
        return Inertia::render('Presentation');
    }
}
