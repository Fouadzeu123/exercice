<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\UserNode;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ---------------------------------------------------------
        // 1. Récupération des Filleuls (Direct Referrals)
        // ---------------------------------------------------------
        $directReferrals = User::where('referrer_id', $user->id)
            ->with(['userNodes' => function ($query) {
                $query->where('active', true);
            }])
            ->with(['transactions' => function ($query) {
                $query->where('type', 'purchase')->where('amount', '<', 0);
            }])
            ->get();

        $referralsFormatted = $directReferrals->map(function ($referral) {
            $hasActiveNode = $referral->userNodes->isNotEmpty();
            $totalInvested = $referral->transactions->sum(function ($transaction) {
                return abs($transaction->amount);
            });

            $phoneHidden = $referral->phone;
            if (strlen($phoneHidden) > 7) {
                $phoneHidden = substr($phoneHidden, 0, 4) . '****' . substr($phoneHidden, -3);
            }

            return [
                'id' => $referral->id,
                'phone' => $phoneHidden,
                'vip_level' => $referral->vip_level,
                'joined_at' => $referral->created_at->format('d/m/Y'),
                'is_active' => $hasActiveNode,
                'total_invested' => (float) $totalInvested,
            ];
        });

        // ---------------------------------------------------------
        // 2. Calcul des filleuls Niveau 2 et Niveau 3
        // ---------------------------------------------------------
        $level1UserIds = User::where('referrer_id', $user->id)->pluck('id');
        $level2UserIds = User::whereIn('referrer_id', $level1UserIds)->pluck('id');
        $level3UserIds = User::whereIn('referrer_id', $level2UserIds)->pluck('id');

        // Filleuls Niveau 2
        $level2Referrals = User::whereIn('id', $level2UserIds)
            ->with(['userNodes' => function ($q) { $q->where('active', true); }])
            ->get()
            ->map(function ($ref) {
                $phone = $ref->phone;
                if (strlen($phone) > 7) {
                    $phone = substr($phone, 0, 4) . '****' . substr($phone, -3);
                }
                return [
                    'id' => $ref->id,
                    'phone' => $phone,
                    'vip_level' => $ref->vip_level,
                    'joined_at' => $ref->created_at->format('d/m/Y'),
                    'is_active' => $ref->userNodes->isNotEmpty(),
                ];
            });

        // Filleuls Niveau 3
        $level3Referrals = User::whereIn('id', $level3UserIds)
            ->with(['userNodes' => function ($q) { $q->where('active', true); }])
            ->get()
            ->map(function ($ref) {
                $phone = $ref->phone;
                if (strlen($phone) > 7) {
                    $phone = substr($phone, 0, 4) . '****' . substr($phone, -3);
                }
                return [
                    'id' => $ref->id,
                    'phone' => $phone,
                    'vip_level' => $ref->vip_level,
                    'joined_at' => $ref->created_at->format('d/m/Y'),
                    'is_active' => $ref->userNodes->isNotEmpty(),
                ];
            });

        // ---------------------------------------------------------
        // 3. Calcul des Métriques (Stats)
        // ---------------------------------------------------------
        $activeMembers = $referralsFormatted->where('is_active', true)->count();
        $teamVolume = $referralsFormatted->sum('total_invested');
        
        $totalCommissions = Transaction::where('user_id', $user->id)
            ->where('type', 'earnings')
            ->where('reference', 'like', 'COM-%')
            ->sum('amount');

        // Calcul dynamique des statistiques de niveau 1, 2 et 3
        $level1Stats = Transaction::whereIn('user_id', $level1UserIds)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount, COUNT(id) as count')
            ->first();

        $level2Stats = Transaction::whereIn('user_id', $level2UserIds)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount, COUNT(id) as count')
            ->first();

        $level3Stats = Transaction::whereIn('user_id', $level3UserIds)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount, COUNT(id) as count')
            ->first();

        $referralLink = route('register') . '?ref=' . $user->referral_code;

        return Inertia::render('Team', [
            'referrals' => $referralsFormatted,
            'level2Referrals' => $level2Referrals,
            'level3Referrals' => $level3Referrals,
            'levelStats' => [
                'level1' => [
                    'amount' => (float) $level1Stats->total_amount,
                    'count' => (int) $level1Stats->count,
                ],
                'level2' => [
                    'amount' => (float) $level2Stats->total_amount,
                    'count' => (int) $level2Stats->count,
                ],
                'level3' => [
                    'amount' => (float) $level3Stats->total_amount,
                    'count' => (int) $level3Stats->count,
                ],
            ],
            'stats' => [
                'total_members' => $referralsFormatted->count(),
                'active_members' => $activeMembers,
                'inactive_members' => $referralsFormatted->count() - $activeMembers,
                'team_volume' => (float) $teamVolume,
                'total_commissions' => (float) $totalCommissions,
                'referral_code' => $user->referral_code,
                'referral_link' => $referralLink,
            ]
        ]);
    }

    /**
     * Rendu de la page de Partage (QR Code & Liens de parrainage)
     */
    public function share()
    {
        $user = Auth::user();
        $referralLink = route('register') . '?ref=' . $user->referral_code;

        $referredCount = User::where('referrer_id', $user->id)->count();

        $commissionsTotal = Transaction::where('user_id', $user->id)
            ->where('type', 'earnings')
            ->where('reference', 'like', 'COM-%')
            ->sum('amount');

        $level1UserIds = User::where('referrer_id', $user->id)->pluck('id');
        $referralsDepositsTotal = Transaction::whereIn('user_id', $level1UserIds)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        return Inertia::render('Share', [
            'stats' => [
                'referral_code' => $user->referral_code,
                'referral_link' => $referralLink,
                'referred_count' => (int) $referredCount,
                'commissions_total' => (float) $commissionsTotal,
                'referrals_deposits_total' => (float) $referralsDepositsTotal,
            ]
        ]);
    }
}