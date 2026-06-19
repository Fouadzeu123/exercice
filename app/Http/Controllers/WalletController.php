<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use DB;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $totalDeposits = Transaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalWithdrawals = Transaction::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');

        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Wallet', [
            'totalDeposits' => (float) abs($totalDeposits),
            'totalWithdrawals' => (float) abs($totalWithdrawals),
            'transactions' => $transactions,
            'defaultAction' => $request->query('action', 'none'),
        ]);
    }

    /**
     * Show Deposit / Recharger Dedicated Page with deposit history
     */
    public function rechargerPage()
    {
        $user = Auth::user();

        $deposits = Transaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Recharger', [
            'deposits' => $deposits,
        ]);
    }

    /**
     * Show Withdraw / Retirer Dedicated Page with withdrawal history
     */
    public function retirerPage()
    {
        $user = Auth::user();

        $withdrawals = Transaction::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $methods = \App\Models\WithdrawalMethod::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->get();

        return Inertia::render('Retirer', [
            'withdrawals' => $withdrawals,
            'withdrawalMethods' => $methods,
            'hasWithdrawalPassword' => !is_null($user->withdrawal_password),
            'hasInvested' => \App\Models\UserNode::where('user_id', $user->id)->exists(),
        ]);
    }

    /**
     * Handle Deposit Request (NotchPay removed)
     */
    public function deposit(Request $request)
    {
        $minDeposit = \App\Services\SettingsService::get('min_deposit', 1000);
        $request->validate([
            'amount' => 'required|numeric|min:' . $minDeposit,
            'method' => 'required|string|in:mtn,orange',
            'phone' => 'nullable|string',
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $reference = 'DEP-' . strtoupper(bin2hex(random_bytes(4)));

        // Auto-detect operator based on Cameroon phone prefix
        $detectedMethod = $request->input('method');
        $cleanPhone = ltrim(trim($request->phone), '+');
        if (str_starts_with($cleanPhone, '237')) {
            $cleanPhone = substr($cleanPhone, 3);
        }

        if (preg_match('/^(69|655|656|657|658|659)/', $cleanPhone)) {
            $detectedMethod = 'orange';
        } elseif (preg_match('/^(67|68|650|651|652|653|654)/', $cleanPhone)) {
            $detectedMethod = 'mtn';
        }

        try {
            DB::transaction(function () use ($user, $amount, $reference, $request, $detectedMethod) {
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'deposit',
                    'status' => 'pending',
                    'reference' => $reference,
                    'payment_method' => $detectedMethod,
                    'payment_phone' => $request->phone,
                ]);
            });

            // For MTN or Orange, integrate Fapshi API
            $apiUser = config('services.fapshi.api_user');
            $apiKey = config('services.fapshi.api_key');

            if (!$apiUser || !$apiKey) {
                if (app()->environment('local')) {
                    $transId = 'mock-' . uniqid();
                    $transaction = Transaction::where('reference', $reference)->first();
                    if ($transaction) {
                        $transaction->gateway_ref = $transId;
                        $transaction->save();
                    }
                    return redirect()->route('fapshi.pay', ['reference' => $reference]);
                }
                return back()->withErrors(['error' => 'Configuration Fapshi manquante ou incorrecte (Clés API User/API Key absentes).']);
            }

            // Make request to Fapshi API (ignore SSL verification locally to prevent local issuer certificate issues)
            $apiUrl = (config('services.fapshi.api_url') ?: 'https://sandbox.fapshi.com') . '/initiate-pay';

            \Illuminate\Support\Facades\Log::info('Fapshi Initiate Pay Request:', [
                'amount' => $amount,
                'email' => $user->email,
                'reference' => $reference
            ]);

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'apiuser' => $apiUser,
                    'apikey' => $apiKey,
                ])
                ->post($apiUrl, [
                    'amount' => (int)$amount,
                    'email' => $user->email,
                    'userId' => (string)$user->id,
                    'externalId' => $reference,
                    'redirectUrl' => route('wallet.recharger'),
                    'message' => 'Dépôt ' . $reference,
                ]);

            if ($response->successful()) {
                $transId = $response->json('transId');
                $link = $response->json('link');
                if ($transId && $link) {
                    $transaction = Transaction::where('reference', $reference)->first();
                    if ($transaction) {
                        $transaction->gateway_ref = $transId;
                        $transaction->save();
                    }
                    return Inertia::location($link);
                }
                return back()->withErrors(['error' => 'L\'identifiant ou le lien de transaction est manquant dans la réponse de Fapshi.']);
            }

            $errorMsg = $response->json('message') ?? $response->json('error') ?? 'Impossible d\'initialiser le paiement (Erreur HTTP ' . $response->status() . ').';
            return back()->withErrors(['error' => 'Échec Fapshi : ' . $errorMsg]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Fapshi Pay error: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Erreur de communication avec Fapshi : ' . $e->getMessage()]);
        }
    }

    /**
     * Handle Withdrawal Request
     */
    public function withdraw(Request $request)
    {
        $minWithdrawal = \App\Services\SettingsService::get('min_withdrawal', 1000);
        $request->validate([
            'amount' => 'required|numeric|min:' . $minWithdrawal,
            'method' => 'required|string|in:mtn,orange,usdt',
            'phone' => 'nullable|string',
            'wallet_address' => 'nullable|string',
            'withdrawal_password' => 'required|string',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        // 1. Invested in at least one product
        $hasInvested = \App\Models\UserNode::where('user_id', $user->id)->exists();
        if (!$hasInvested) {
            return back()->withErrors(['error' => 'Vous devez avoir investi dans au moins un produit pour effectuer un retrait.']);
        }

        // 2. Configured withdrawal methods
        $hasMethods = \App\Models\WithdrawalMethod::where('user_id', $user->id)->exists();
        if (!$hasMethods) {
            return back()->withErrors(['error' => 'Vous devez avoir configuré vos numéros mobiles de retrait dans vos paramètres.']);
        }

        // 3. Configured withdrawal password
        if (!$user->withdrawal_password) {
            return back()->withErrors(['error' => 'Vous devez configurer votre mot de passe de retrait (Code PIN) dans vos paramètres.']);
        }

        // Validate withdrawal password
        if (!\Hash::check($request->withdrawal_password, $user->withdrawal_password)) {
            return back()->withErrors(['error' => 'Le mot de passe de retrait saisi est incorrect.']);
        }

        if ($user->balance < $amount) {
            return back()->withErrors(['error' => 'Solde insuffisant pour effectuer ce retrait.']);
        }

        $reference = 'WTH-' . strtoupper(bin2hex(random_bytes(4)));

        try {
            DB::transaction(function () use ($user, $amount, $reference, $request) {
                $user->balance -= $amount;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => -$amount,
                    'type' => 'withdrawal',
                    'status' => 'pending',
                    'reference' => $reference,
                    'payment_method' => $request->method,
                    'payment_phone' => $request->method === 'usdt' ? $request->wallet_address : $request->phone,
                ]);
            });

            return redirect()->route('wallet.retirer')->with('success', 'Demande de retrait soumise avec succès.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Échec de la demande: ' . $e->getMessage()]);
        }
    }

    /**
     * Réclame un code cadeau promotionnel (Gift Code).
     */
    public function claimGift(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $code = strtoupper(trim($request->code));

        $giftCode = \App\Models\GiftCode::where('code', $code)->first();

        if (!$giftCode) {
            return back()->withErrors(['code' => 'Code cadeau invalide, corrompu ou expiré.']);
        }

        if ($giftCode->usages >= $giftCode->max_usages) {
            return back()->withErrors(['code' => 'Ce code cadeau a atteint sa limite maximale d\'utilisations.']);
        }

        $amount = $giftCode->amount;
        $reference = 'GIFT-' . $code;

        $alreadyClaimed = Transaction::where('user_id', $user->id)
            ->where('reference', $reference)
            ->exists();

        if ($alreadyClaimed) {
            return back()->withErrors(['code' => 'Ce code cadeau a déjà été synchronisé sur ce nœud.']);
        }

        try {
            DB::transaction(function () use ($user, $amount, $reference, $giftCode) {
                $user->balance += $amount;
                $user->save();

                $giftCode->increment('usages');

                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'earnings',
                    'status' => 'completed',
                    'reference' => $reference,
                ]);
            });

            return redirect()->back()->with('success', 'Code cadeau échangé avec succès ! +' . $amount . ' XAF injectés.');

        } catch (\Exception $e) {
            return back()->withErrors(['code' => 'Échec de la réclamation : ' . $e->getMessage()]);
        }
    }

    /**
     * Show dedicated Deposits History page
     */
    public function rechargesHistoryPage()
    {
        $user = Auth::user();

        $deposits = Transaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Recharges', [
            'deposits' => $deposits,
        ]);
    }

    /**
     * Show dedicated Withdrawals History page
     */
    public function retraitsHistoryPage()
    {
        $user = Auth::user();

        $withdrawals = Transaction::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Retraits', [
            'withdrawals' => $withdrawals,
        ]);
    }



    /**
     * Handles real callback notifications from Fapshi API.
     */
    public function webhook(Request $request)
    {
        $secret = config('services.fapshi.webhook_secret', 'fapshi-secret-key');
        $signature = $request->header('x-wh-secret');

        if (!$signature || $signature !== $secret) {
            return response()->json(['message' => 'Invalid webhook secret'], 401);
        }

        $transId = $request->input('transId');
        $status = $request->input('status');
        $amount = $request->input('amount');
        $externalId = $request->input('externalId');

        $transaction = Transaction::where('reference', $externalId)->first();
        if ($transaction && $transaction->status === 'pending') {
            if ($status === 'SUCCESSFUL') {
                DB::transaction(function () use ($transaction) {
                    $transaction->status = 'completed';
                    $transaction->save();

                    $user = $transaction->user;
                    $user->balance += $transaction->amount;
                    $user->save();
                });
            } elseif (in_array($status, ['FAILED', 'EXPIRED'])) {
                DB::transaction(function () use ($transaction) {
                    $transaction->status = 'rejected';
                    $transaction->save();
                });
            }
        }

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Show custom Fapshi pay page.
     */
    public function fapshiPayPage($reference)
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Get fallback values using SettingsService
        $orangeAgent = \App\Services\SettingsService::get('fapshi_orange_agent', '694196055');
        $orangeMerchant = \App\Services\SettingsService::get('fapshi_orange_merchant', '374320');
        
        $mtnAgent = \App\Services\SettingsService::get('fapshi_mtn_agent', '670000000');
        $mtnMerchant = \App\Services\SettingsService::get('fapshi_mtn_merchant', '123456');

        return Inertia::render('FapshiPayPage', [
            'transaction' => $transaction,
            'settings' => [
                'fapshi_orange_agent' => $orangeAgent,
                'fapshi_orange_merchant' => $orangeMerchant,
                'fapshi_mtn_agent' => $mtnAgent,
                'fapshi_mtn_merchant' => $mtnMerchant,
            ]
        ]);
    }

    /**
     * Polling method to check status of a Fapshi transaction.
     */
    public function checkFapshiStatus($reference)
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($transaction->status !== 'pending') {
            return response()->json([
                'status' => $transaction->status,
                'message' => 'La transaction est déjà traitée.'
            ]);
        }

        $transId = $transaction->gateway_ref;
        $apiUser = config('services.fapshi.api_user');
        $apiKey = config('services.fapshi.api_key');

        // Local testing mock fallback
        if (!$transId || str_starts_with($transId, 'mock-') || !$apiUser || !$apiKey) {
            DB::transaction(function () use ($transaction) {
                $transaction->status = 'completed';
                $transaction->save();

                $user = $transaction->user;
                $user->balance += $transaction->amount;
                $user->save();
            });

            return response()->json([
                'status' => 'completed',
                'message' => 'Paiement validé avec succès (Mode Simulation).'
            ]);
        }

        try {
            $apiUrl = (config('services.fapshi.api_url') ?: 'https://sandbox.fapshi.com') . '/payment-status/' . $transId;
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'apiuser' => $apiUser,
                    'apikey' => $apiKey,
                ])
                ->get($apiUrl);

            if ($response->successful()) {
                $fapshiStatus = $response->json('status');

                if ($fapshiStatus === 'SUCCESSFUL') {
                    DB::transaction(function () use ($transaction) {
                        $transaction->status = 'completed';
                        $transaction->save();

                        $user = $transaction->user;
                        $user->balance += $transaction->amount;
                        $user->save();
                    });

                    return response()->json([
                        'status' => 'completed',
                        'message' => 'Dépôt validé avec succès.'
                    ]);
                } elseif (in_array($fapshiStatus, ['FAILED', 'EXPIRED'])) {
                    DB::transaction(function () use ($transaction) {
                        $transaction->status = 'rejected';
                        $transaction->save();
                    });

                    return response()->json([
                        'status' => 'rejected',
                        'message' => 'Le paiement a échoué ou a expiré.'
                    ]);
                } else {
                    return response()->json([
                        'status' => 'pending',
                        'message' => 'Le paiement est toujours en attente.'
                    ]);
                }
            }

            return response()->json([
                'status' => 'pending',
                'message' => 'Impossible de vérifier le statut auprès de Fapshi pour le moment.'
            ], 500);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Check Fapshi Status error: ' . $e->getMessage());
            return response()->json([
                'status' => 'pending',
                'message' => 'Erreur de communication avec Fapshi.'
            ], 500);
        }
    }
}
