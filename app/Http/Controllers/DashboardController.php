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
     */
    public function checkin()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Référence unique par jour : CKI-YYYYMMDD
        $reference = 'CKI-' . $today->format('Ymd');
        $alreadyCheckedIn = Transaction::where('user_id', $user->id)
            ->where('type', 'earnings')
            ->where('reference', $reference)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'success' => false,
                'error' => 'Votre console principale a déjà été synchronisée pour aujourd\'hui. Veuillez revenir demain.'
            ], 400);
        }

        try {
            \DB::transaction(function () use ($user, $reference) {
                // Montant du bonus quotidien : 77 FCFA
                $bonusAmount = 77.00;

                // Ajouter au solde
                $user->balance += $bonusAmount;
                $user->save();

                // Créer la transaction de pointage
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $bonusAmount,
                    'type' => 'earnings',
                    'status' => 'completed',
                    'reference' => $reference,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Synchronisation du nœud principal réussie. +77 FCFA injectés.',
                'new_balance' => $user->balance
            ]);

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

        return Inertia::render('Commandes', [
            'orders' => $userNodes,
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
            ->where('type', 'earnings')
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
            $product->duration = 7;
            $product->generation_profit = (float) $product->daily_salary;
        } else {
            abort(404, 'Type de produit inconnu');
        }

        $activeReferralsCount = \App\Models\User::where('referrer_id', $user->id)
            ->where(function($query) {
                $query->whereHas('userNodes')
                      ->orWhereHas('userAVIPProducts');
            })
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

        if ($user->draw_spins < 1) {
            return response()->json([
                'success' => false,
                'error' => "Vous n'avez plus de lancers disponibles. Les lancers sont attribués par le service RH."
            ], 400);
        }

        try {
            return \DB::transaction(function () use ($user) {
                // Déduire le lancer
                $user->draw_spins -= 1;

                // Définir les gains et leurs probabilités cumulées (Total = 1000)
                $prizes = [777, 1777, 7777, 77777, 177777, 777777, 1777777];
                $weights = [600, 250, 100, 35, 12, 2, 1];
                
                $winnerIndex = null;
                
                if ($user->next_spin_prize_index !== null && $user->next_spin_prize_index >= 0 && $user->next_spin_prize_index <= 6) {
                    $winnerIndex = $user->next_spin_prize_index;
                    // Reset rigging immediately
                    $user->next_spin_prize_index = null;
                } else {
                    $winnerIndex = 0; // Default always to 777
                }

                $wonAmount = (float) $prizes[$winnerIndex];

                // Ajouter le gain
                $user->balance += $wonAmount;
                $user->save();

                // Créer l'historique de transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $wonAmount,
                    'type' => 'earnings',
                    'status' => 'completed',
                    'reference' => 'DRAW-' . strtoupper(bin2hex(random_bytes(4))),
                ]);

                return response()->json([
                    'success' => true,
                    'won_amount' => $wonAmount,
                    'winner_index' => $winnerIndex,
                    'new_balance' => $user->balance,
                    'draw_spins' => $user->draw_spins,
                    'message' => "Félicitations ! Vous avez gagné {$wonAmount} XAF !"
                ]);
            });

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
