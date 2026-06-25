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

        // Enforce required active referrals constraint
        if ($product->required_active_referrals > 0) {
            $activeReferralsCount = \App\Models\User::where('referrer_id', $user->id)
                ->hasActiveInvestments()
                ->count();
            if ($activeReferralsCount < $product->required_active_referrals) {
                return back()->withErrors(['error' => "Ce produit requiert au moins {$product->required_active_referrals} filleul(s) actif(s) ayant loué/acheté un produit pour être déverrouillé. Vous en avez actuellement : {$activeReferralsCount}."]);
            }
        }

        // Validation: User must have the required VIP level
        $userVipLevel = $user->vip_level ?? 0;
        if ($userVipLevel < $product->required_vip_level) {
            return back()->withErrors(['error' => "Vous devez être VIP {$product->required_vip_level} minimum pour acheter ce produit AVIP. Votre niveau actuel : VIP {$userVipLevel}."]);
        }

        // Enforce limited offer stock limit
        if ($product->stock_quantity !== null) {
            $soldCount = UserAVIPProduct::where('avip_product_id', $product->id)
                ->where('active', true)
                ->count();
            if ($soldCount >= $product->stock_quantity) {
                return back()->withErrors(['error' => 'Cette offre AVIP est épuisée (rupture de stock).']);
            }
        }

        // Check if user already owns this AVIP product, unless a limited quota is defined
        $userPurchaseCount = UserAVIPProduct::where('user_id', $user->id)
            ->where('avip_product_id', $product->id)
            ->where('active', true)
            ->count();

        if ($product->limited_purchase_count !== null) {
            if ($userPurchaseCount >= $product->limited_purchase_count) {
                return back()->withErrors(['error' => "Vous avez atteint le quota maximal autorisé pour ce produit AVIP ({$product->limited_purchase_count} par compte)."]);
            }
        } else {
            // Default behavior: max 1 per user
            if ($userPurchaseCount >= 1) {
                return back()->withErrors(['error' => 'Vous possédez déjà ce produit AVIP.']);
            }
        }

        // Check if user has sufficient balance
        if ($user->balance < $product->amount) {
            return back()->withErrors(['error' => 'Solde insuffisant pour acheter ce produit AVIP.']);
        }

        try {
            DB::transaction(function () use ($user, $product) {
                // Lock and load fresh user record to avoid concurrent purchases balance bypass
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                if (!$lockedUser || $lockedUser->balance < $product->amount) {
                    throw new \Exception('Solde insuffisant pour acheter ce produit AVIP.');
                }

                // Check if the user has already purchased this AVIP product in the past
                $alreadyPurchased = UserAVIPProduct::where('user_id', $lockedUser->id)
                    ->where('avip_product_id', $product->id)
                    ->exists();

                // Deduct the cost from user's balance
                $lockedUser->balance -= $product->amount;
                $lockedUser->save();

                // Create the user AVIP product purchase
                UserAVIPProduct::create([
                    'user_id' => $lockedUser->id,
                    'avip_product_id' => $product->id,
                    'amount' => $product->amount,
                    'active' => true,
                    'purchased_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addDays($product->duration ?? 7),
                ]);

                // Log the purchase transaction
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'amount' => -$product->amount,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'reference' => 'AVIP-' . strtoupper(bin2hex(random_bytes(4))),
                ]);

                // Recalculate VIP/AVIP status
                $lockedUser->recalculateVipAndAvipStatus();

                // Pay referral commission if the user has a referrer and hasn't purchased this specific AVIP product before
                if ($lockedUser->referrer_id && !$alreadyPurchased) {
                    $sponsor = \App\Models\User::where('id', $lockedUser->referrer_id)->lockForUpdate()->first();
                    if ($sponsor) {
                        $commissionAmount = (float)($product->referral_reward ?? 0.00);

                        if ($commissionAmount > 0) {
                            // Credit sponsor balance
                            $sponsor->balance += $commissionAmount;
                            $sponsor->save();

                            // Log commission transaction
                            Transaction::create([
                                'user_id' => $sponsor->id,
                                'amount' => $commissionAmount,
                                'type' => 'commission',
                                'status' => 'completed',
                                'reference' => 'COM-' . strtoupper(bin2hex(random_bytes(4))),
                            ]);

                            // Recalculate status for sponsor
                            $sponsor->recalculateVipAndAvipStatus();
                        }
                    }
                }
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
        if (Carbon::now()->isWeekend()) {
            return response()->json(['error' => 'Les réclamations de salaire journalier sont disponibles uniquement du lundi au vendredi.'], 422);
        }

        $user = Auth::user();
        $userVipLevel = $user->vip_level ?? 0;

        if ($userVipLevel < 1) {
            return response()->json(['error' => 'Les membres de niveau VIP 0 n\'ont pas de salaire journalier. Veuillez activer au moins un nœud de calcul (VIP 1) pour commencer à percevoir des dividendes journaliers.'], 422);
        }

        // Define daily salary amounts per VIP level dynamically from settings
        $dailySalaries = \App\Services\SettingsService::get('vip_salaries');
        $salaryAmount = $dailySalaries[$userVipLevel] ?? 0.00;

        try {
            DB::transaction(function () use ($user, $salaryAmount) {
                // Lock and load fresh user record
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                $userVipLevel = $lockedUser->vip_level ?? 0;

                if ($userVipLevel < 1) {
                    throw new \InvalidArgumentException('Les membres de niveau VIP 0 n\'ont pas de salaire journalier. Veuillez activer au moins un nœud de calcul (VIP 1) pour commencer à percevoir des dividendes journaliers.');
                }

                $salaryClaimsCount = Transaction::where('user_id', $lockedUser->id)
                    ->where('type', 'salary')
                    ->where('status', 'completed')
                    ->count();

                // VIP 1 limit to 7 claims
                if ($userVipLevel === 1 && $salaryClaimsCount >= 7) {
                    throw new \InvalidArgumentException('Vous avez déjà réclamé vos 7 jours de salaire pour le niveau VIP 1.');
                }

                // VIP 2 limit to 30 claims
                if ($userVipLevel === 2 && $salaryClaimsCount >= 30) {
                    throw new \InvalidArgumentException('Vous avez déjà réclamé vos 30 jours de salaire pour le niveau VIP 2.');
                }

                // VIP 3 limit to 30 claims
                if ($userVipLevel === 3 && $salaryClaimsCount >= 30) {
                    throw new \InvalidArgumentException('Vous avez déjà réclamé vos 30 jours de salaire pour le niveau VIP 3.');
                }

                // 1. Enforce first salary claim only 24 hours after the first purchased product (node or avip)
                $earliestProduct = DB::table('user_nodes')
                    ->where('user_id', $lockedUser->id)
                    ->where('active', true)
                    ->orderBy('activated_at', 'asc')
                    ->first();

                $earliestAvipProduct = DB::table('user_avip_products')
                    ->where('user_id', $lockedUser->id)
                    ->where('active', true)
                    ->orderBy('purchased_at', 'asc')
                    ->first();

                $activationTime = null;
                if ($earliestProduct && $earliestAvipProduct) {
                    $activationTime = Carbon::min(Carbon::parse($earliestProduct->activated_at), Carbon::parse($earliestAvipProduct->purchased_at));
                } elseif ($earliestProduct) {
                    $activationTime = Carbon::parse($earliestProduct->activated_at);
                } elseif ($earliestAvipProduct) {
                    $activationTime = Carbon::parse($earliestAvipProduct->purchased_at);
                }

                if ($activationTime && Carbon::parse($activationTime)->addHours(24)->isFuture()) {
                    $availableTime = Carbon::parse($activationTime)->addHours(24);
                    throw new \InvalidArgumentException("Vous pourrez réclamer votre premier salaire journalier 24 heures après l'achat de votre premier produit. Disponible le : " . $availableTime->format('d/m/Y H:i:s'));
                }

                // 2. Enforce subsequent claims only 24 hours after the last salary claim
                $lastClaimDate = $lockedUser->last_salary_claim_date;
                if ($lastClaimDate && Carbon::parse($lastClaimDate)->addHours(24)->isFuture()) {
                    $nextAvailable = Carbon::parse($lastClaimDate)->addHours(24);
                    throw new \InvalidArgumentException("Vous ne pouvez réclamer votre salaire qu'une seule fois toutes les 24 heures. Prochaine réclamation disponible à partir de : " . $nextAvailable->format('d/m/Y H:i:s'));
                }

                // Add salary to user balance
                $lockedUser->balance += $salaryAmount;
                $lockedUser->last_salary_claim_date = Carbon::now();
                $lockedUser->save();

                // Log the salary transaction
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'amount' => $salaryAmount,
                    'type' => 'salary',
                    'status' => 'completed',
                    'reference' => 'SAL-' . strtoupper(bin2hex(random_bytes(4))),
                ]);

                // Distribute daily referral commissions
                $lockedUser->payDailyCommissions($salaryAmount);
            });

            $user->refresh();

            return response()->json([
                'success' => true,
                'salary_amount' => $salaryAmount,
                'new_balance' => $user->balance
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la réclamation du salaire : ' . $e->getMessage()], 500);
        }
    }
}
