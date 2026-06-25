<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use DB;
use App\Services\NotchPayService;

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
        $minDeposit = \App\Services\SettingsService::get('min_deposit', 500);
        $request->validate([
            'amount' => 'required|numeric|min:' . $minDeposit,
            'method' => 'required|string|in:mtn,orange',
            'phone' => 'nullable|string',
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $reference = 'DEP-' . strtoupper(bin2hex(random_bytes(4)));

        $detectedMethod = $request->input('method');

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

            // For MTN or Orange, integrate Notch Pay API
            $publicKey = config('services.notchpay.public_key');
            $secretKey = config('services.notchpay.secret_key');

            if (!$publicKey || !$secretKey) {
                if (app()->environment('local')) {
                    $transId = 'mock-' . uniqid();
                    $transaction = Transaction::where('reference', $reference)->first();
                    if ($transaction) {
                        $transaction->gateway_ref = $transId;
                        $transaction->save();
                    }
                    return redirect()->route('notchpay.pay', ['reference' => $reference]);
                }
                return back()->withErrors(['error' => 'Configuration Notch Pay manquante ou incorrecte (Clés API absentes).']);
            }

            // Clean phone
            $phone = trim($request->phone);
            if (!str_starts_with($phone, '+')) {
                if (str_starts_with($phone, '237')) {
                    $phone = '+' . $phone;
                } else {
                    $phone = '+237' . $phone;
                }
            }

            // Initialize payment via Notch Pay
            $notchPayService = app(NotchPayService::class);

            $paymentResponse = $notchPayService->initializePayment([
                'amount' => (int)$amount,
                'currency' => 'XAF',
                'email' => $user->email ?: (str_replace('+', '', $phone) . '@armicm.com'),
                'phone' => $phone,
                'reference' => $reference,
                'description' => 'Dépôt ' . $reference,
            ]);

            $gatewayRef = null;
            if (isset($paymentResponse->transaction)) {
                if (is_object($paymentResponse->transaction)) {
                    $gatewayRef = $paymentResponse->transaction->reference ?? null;
                } else {
                    $gatewayRef = $paymentResponse->transaction;
                }
            }

            if (!$gatewayRef) {
                return back()->withErrors(['error' => 'L\'identifiant ou le lien de transaction est manquant dans la réponse de Notch Pay.']);
            }

            $transaction = Transaction::where('reference', $reference)->first();
            if ($transaction) {
                $transaction->gateway_ref = $gatewayRef;
                $transaction->save();
            }

            return redirect()->route('notchpay.pay', ['reference' => $reference]);

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($e instanceof \NotchPay\Exceptions\ApiException && !empty($e->errors)) {
                $msg .= ' : ' . json_encode($e->errors);
            }
            \Illuminate\Support\Facades\Log::error('Notch Pay error: ' . $msg);
            return back()->withErrors(['error' => 'Échec Notch Pay : ' . $msg]);
        }
    }

    /**
     * Handle Withdrawal Request
     */
    public function withdraw(Request $request)
    {
        $minWithdrawal = \App\Services\SettingsService::get('min_withdrawal', 1500);
        $request->validate([
            'amount' => 'required|numeric|min:' . $minWithdrawal,
            'method' => 'required|string|in:mtn,orange,usdt',
            'phone' => 'nullable|string',
            'wallet_address' => 'nullable|string',
            'withdrawal_password' => 'required|string',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        // Validation stricte des jours et horaires de retrait (Lundi - Vendredi, 09h00 - 17h30, heure du Cameroun)
        $now = \Carbon\Carbon::now('Africa/Douala');
        if ($now->isWeekend()) {
            return back()->withErrors(['error' => 'Les retraits sont ouverts uniquement du lundi au vendredi de 09h00 à 17h30 (Heure du Cameroun).']);
        }

        $startTime = $now->copy()->setTime(9, 0, 0);
        $endTime = $now->copy()->setTime(17, 30, 0);
        if (!$now->between($startTime, $endTime)) {
            return back()->withErrors(['error' => 'Les retraits sont ouverts uniquement du lundi au vendredi de 09h00 à 17h30 (Heure du Cameroun).']);
        }

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

        // Quick pre-check
        if ($user->balance < $amount) {
            return back()->withErrors(['error' => 'Solde insuffisant pour effectuer ce retrait.']);
        }

        $reference = 'WTH-' . strtoupper(bin2hex(random_bytes(4)));

        try {
            DB::transaction(function () use ($user, $amount, $reference, $request) {
                // Lock user record for update to prevent concurrent double-withdrawals
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                if (!$lockedUser || $lockedUser->balance < $amount) {
                    throw new \Exception('Solde insuffisant pour effectuer ce retrait.');
                }

                $lockedUser->balance -= $amount;
                $lockedUser->save();

                Transaction::create([
                    'user_id' => $lockedUser->id,
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

        try {
            DB::transaction(function () use ($user, $code, &$amount) {
                // Lock the gift code record
                $giftCode = \App\Models\GiftCode::where('code', $code)->lockForUpdate()->first();

                if (!$giftCode) {
                    throw new \InvalidArgumentException('Code cadeau invalide, corrompu ou expiré.');
                }

                if ($giftCode->usages >= $giftCode->max_usages) {
                    throw new \InvalidArgumentException('Ce code cadeau a atteint sa limite maximale d\'utilisations.');
                }

                $amount = $giftCode->amount;
                $reference = 'GIFT-' . $code;

                $alreadyClaimed = Transaction::where('user_id', $user->id)
                    ->where('reference', $reference)
                    ->exists();

                if ($alreadyClaimed) {
                    throw new \InvalidArgumentException('Ce code cadeau a déjà été synchronisé sur ce nœud.');
                }

                // Lock user record
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                $lockedUser->balance += $amount;
                $lockedUser->save();

                $giftCode->usages += 1;
                $giftCode->save();

                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'amount' => $amount,
                    'type' => 'earnings',
                    'status' => 'completed',
                    'reference' => $reference,
                ]);
            });

            return redirect()->back()->with('success', 'Code cadeau échangé avec succès ! +' . $amount . ' XAF injectés.');

        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
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
        $secret = config('services.notchpay.webhook_secret');
        $signature = $request->header('x-notch-signature');

        $payload = $request->getContent();

        if (!$signature || !$secret || !hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event') ?? $request->input('type');
        $data = $request->input('data') ?? [];
        $externalId = $data['reference'] ?? null; // Notch Pay reference (trx.xxx)
        $merchantRef = $data['merchant_reference'] ?? $data['trxref'] ?? null; // Our reference (DEP-xxx)
        $status = $data['status'] ?? null;

        if (!$externalId && !$merchantRef) {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        // Search by gateway_ref first (trx.xxx), then fallback to internal reference (DEP-xxx)
        $transaction = null;
        if ($externalId) {
            $transaction = Transaction::where('gateway_ref', $externalId)->first();
            if (!$transaction) {
                $transaction = Transaction::where('reference', $externalId)->first();
            }
        }
        if (!$transaction && $merchantRef) {
            $transaction = Transaction::where('reference', $merchantRef)->first();
        }

        if ($transaction && $transaction->status === 'pending') {
            if ($event === 'payment.complete' || $status === 'complete') {
                DB::transaction(function () use ($transaction) {
                    $lockedTrx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                    if (!$lockedTrx || $lockedTrx->status !== 'pending') {
                        return;
                    }
                    $lockedTrx->status = 'completed';
                    $lockedTrx->save();

                    $user = \App\Models\User::where('id', $lockedTrx->user_id)->lockForUpdate()->first();
                    if ($user) {
                        $user->balance += $lockedTrx->amount;
                        $user->save();
                    }
                });
            } elseif (in_array($event, ['payment.failed', 'payment.canceled', 'payment.expired']) || in_array($status, ['failed', 'canceled', 'expired'])) {
                DB::transaction(function () use ($transaction) {
                    $lockedTrx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                    if ($lockedTrx && $lockedTrx->status === 'pending') {
                        $lockedTrx->status = 'rejected';
                        $lockedTrx->save();
                    }
                });
            }
        }

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Show custom Fapshi pay page.
     */
    public function notchPayPage($reference)
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Get fallback values using SettingsService
        $orangeAgent = \App\Services\SettingsService::get('fapshi_orange_agent', '694196055');
        $orangeMerchant = \App\Services\SettingsService::get('fapshi_orange_merchant', '374320');

        $mtnAgent = \App\Services\SettingsService::get('fapshi_mtn_agent', '670000000');
        $mtnMerchant = \App\Services\SettingsService::get('fapshi_mtn_merchant', '123456');

        return Inertia::render('NotchPayPage', [
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
     * Complete the payment charge by submitting user-confirmed operator and number.
     */
    public function chargeNotchPay(Request $request, $reference)
    {
        $request->validate([
            'method' => 'required|string|in:mtn,orange',
            'phone' => 'required|string',
        ]);

        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $phone = trim($request->phone);
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '237')) {
                $phone = '+' . $phone;
            } else {
                $phone = '+237' . $phone;
            }
        }

        $method = $request->input('method');
        $channel = $method === 'orange' ? 'cm.orange' : 'cm.mtn';

        // Update the transaction details in the local DB
        $transaction->payment_method = $method;
        $transaction->payment_phone = $request->phone;
        $transaction->save();

        $transId = $transaction->gateway_ref;
        $secretKey = config('services.notchpay.secret_key');

        // Local testing mock fallback
        if (!$transId || str_starts_with($transId, 'mock-') || !$secretKey) {
            return response()->json([
                'status' => 'success',
                'message' => 'Paiement simulé avec succès (Mode Simulation).'
            ]);
        }

        try {
            $notchPayService = app(NotchPayService::class);
            $notchPayService->chargePayment($transId, [
                'channel' => $channel,
                'data' => [
                    'phone' => $phone,
                ],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification de paiement envoyée.'
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($e instanceof \NotchPay\Exceptions\ApiException && !empty($e->errors)) {
                $msg .= ' : ' . json_encode($e->errors);
            }
            \Illuminate\Support\Facades\Log::error('Notch Pay charge error: ' . $msg);
            return response()->json([
                'status' => 'error',
                'message' => 'Échec Notch Pay : ' . $msg
            ], 422);
        }
    }

    /**
     * Polling method to check status of a Fapshi transaction.
     */
    public function checkNotchPayStatus($reference)
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
        $secretKey = config('services.notchpay.secret_key');

        // Local testing mock fallback
        if (!$transId || str_starts_with($transId, 'mock-') || !$secretKey) {
            DB::transaction(function () use ($transaction) {
                $lockedTrx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                if (!$lockedTrx || $lockedTrx->status !== 'pending') {
                    return;
                }
                $lockedTrx->status = 'completed';
                $lockedTrx->save();

                $user = \App\Models\User::where('id', $lockedTrx->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->balance += $lockedTrx->amount;
                    $user->save();
                }
            });

            return response()->json([
                'status' => 'completed',
                'message' => 'Paiement validé avec succès (Mode Simulation).'
            ]);
        }

        try {
            $notchPayService = app(NotchPayService::class);
            $paymentResponse = $notchPayService->verifyPayment($transId);

            $notchStatus = $paymentResponse->status ?? 'pending';

            if ($notchStatus === 'complete') {
                DB::transaction(function () use ($transaction) {
                    $lockedTrx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                    if (!$lockedTrx || $lockedTrx->status !== 'pending') {
                        return;
                    }
                    $lockedTrx->status = 'completed';
                    $lockedTrx->save();

                    $user = \App\Models\User::where('id', $lockedTrx->user_id)->lockForUpdate()->first();
                    if ($user) {
                        $user->balance += $lockedTrx->amount;
                        $user->save();
                    }
                });

                return response()->json([
                    'status' => 'completed',
                    'message' => 'Dépôt validé avec succès.'
                ]);
            } elseif (in_array($notchStatus, ['failed', 'canceled', 'expired'])) {
                DB::transaction(function () use ($transaction) {
                    $lockedTrx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                    if ($lockedTrx && $lockedTrx->status === 'pending') {
                        $lockedTrx->status = 'rejected';
                        $lockedTrx->save();
                    }
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

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Check NotchPay Status error: ' . $e->getMessage());
            return response()->json([
                'status' => 'pending',
                'message' => 'Erreur de communication avec Notch Pay.'
            ], 500);
        }
    }
}
