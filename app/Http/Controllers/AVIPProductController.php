<?php

namespace App\Http\Controllers;

use App\Models\AVIPProduct;
use App\Models\UserAVIPProduct;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class AVIPProductController extends Controller
{
    /**
     * Display a listing of AVIP products.
     */
    public function index()
    {
        $user = Auth::user();
        $products = AVIPProduct::where('active', true)->orderBy('avip_level', 'asc')->get();

        // Get user's purchased AVIP products
        $userProducts = UserAVIPProduct::where('user_id', $user->id)
            ->where('active', true)
            ->with('avipProduct')
            ->get();

        return Inertia::render('AVIPProducts', [
            'products' => $products,
            'userProducts' => $userProducts,
            'userVipLevel' => $user->vip_level ?? 0,
        ]);
    }

    /**
     * Purchase an AVIP product.
     */
    public function purchase(Request $request, $id)
    {
        $user = Auth::user();
        $product = AVIPProduct::findOrFail($id);

        if (!$product->active) {
            return back()->withErrors(['error' => 'Ce produit AVIP n\'est pas disponible.']);
        }

        // Validation: User must have the required VIP level
        $userVipLevel = $user->vip_level ?? 0;
        if ($userVipLevel < $product->required_vip_level) {
            return back()->withErrors(['error' => "Vous devez être VIP {$product->required_vip_level} minimum pour acheter ce produit AVIP. Votre niveau actuel : VIP {$userVipLevel}."]);
        }

        // Check if user already owns this AVIP product
        $existingPurchase = UserAVIPProduct::where('user_id', $user->id)
            ->where('avip_product_id', $product->id)
            ->where('active', true)
            ->first();

        if ($existingPurchase) {
            return back()->withErrors(['error' => 'Vous possédez déjà ce produit AVIP.']);
        }

        // Check if user has sufficient balance
        if ($user->balance < $product->amount) {
            return back()->withErrors(['error' => 'Solde insuffisant pour acheter ce produit AVIP.']);
        }

        try {
            DB::transaction(function () use ($user, $product) {
                // Deduct the cost from user's balance
                $user->balance -= $product->amount;
                $user->save();

                // Create the user AVIP product purchase
                UserAVIPProduct::create([
                    'user_id' => $user->id,
                    'avip_product_id' => $product->id,
                    'amount' => $product->amount,
                    'active' => true,
                    'purchased_at' => Carbon::now(),
                ]);

                // Log the purchase transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => -$product->amount,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'reference' => 'AVIP-' . strtoupper(bin2hex(random_bytes(4))),
                ]);

                // Recalculate VIP/AVIP status
                $user->recalculateVipAndAvipStatus();
            });

            return redirect()->back()->with('success', 'Produit AVIP acheté avec succès.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'achat : ' . $e->getMessage()]);
        }
    }

    /**
     * Claim daily salary based on VIP level.
     */
    public function claimSalary(Request $request)
    {
        $user = Auth::user();
        $userVipLevel = $user->vip_level ?? 0;

        if ($userVipLevel < 1) {
            return back()->withErrors(['error' => 'Les membres de niveau VIP 0 n\'ont pas de salaire journalier. Veuillez activer au moins un nœud de calcul (VIP 1) pour commencer à percevoir des dividendes journaliers.']);
        }

        // Define daily salary amounts per VIP level
        $dailySalaries = [
            0 => 0.00,
            1 => 100.00,
            2 => 250.00,
            3 => 500.00,
            4 => 1000.00,
            5 => 2000.00,
        ];

        $salaryAmount = $dailySalaries[$userVipLevel] ?? 0.00;

        // Check if user has already claimed salary today
        $lastClaimDate = $user->last_salary_claim_date;
        if ($lastClaimDate && Carbon::parse($lastClaimDate)->isToday()) {
            return back()->withErrors(['error' => 'Vous avez déjà réclamé votre salaire journalier aujourd\'hui.']);
        }

        try {
            DB::transaction(function () use ($user, $salaryAmount) {
                // Add salary to user balance
                $user->balance += $salaryAmount;
                $user->last_salary_claim_date = Carbon::now();
                $user->save();

                // Log the salary transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $salaryAmount,
                    'type' => 'salary',
                    'status' => 'completed',
                    'reference' => 'SAL-' . strtoupper(bin2hex(random_bytes(4))),
                ]);
            });

            return redirect()->route('avip-products.index')->with('success', "Votre salaire de {$salaryAmount} a été réclamé avec succès.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la réclamation du salaire : ' . $e->getMessage()]);
        }
    }
}
