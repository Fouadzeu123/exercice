<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\UserNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VIPController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Calculate stats for VIP/AVIP checks
        $personalInvested = abs(Transaction::where('user_id', $user->id)
            ->where('type', 'purchase')
            ->where('amount', '<', 0)
            ->sum('amount'));

        $directReferrals = User::where('referrer_id', $user->id)->get();
        $totalReferrals = $directReferrals->count();
        
        $activeReferrals = 0;
        $teamVolume = 0.00;

        foreach ($directReferrals as $referral) {
            $hasActiveNode = UserNode::where('user_id', $referral->id)
                ->where('active', true)
                ->exists();
            if ($hasActiveNode) {
                $activeReferrals++;
            }

            // Sum up their total purchases
            $referralPurchases = abs(Transaction::where('user_id', $referral->id)
                ->where('type', 'purchase')
                ->where('amount', '<', 0)
                ->sum('amount'));
            $teamVolume += $referralPurchases;
        }

        return Inertia::render('VIP', [
            'stats' => [
                'personal_invested' => (float)$personalInvested,
                'active_referrals' => $activeReferrals,
                'team_volume' => (float)$teamVolume,
                'vip_level' => $user->vip_level ?? 0,
                'avip_level' => $user->avip_level ?? 0,
            ]
        ]);
    }
}
