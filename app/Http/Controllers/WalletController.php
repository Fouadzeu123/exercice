<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'method' => 'required|string|in:mtn,orange,usdt',
            'phone' => 'nullable|string',
            'usdt_hash' => 'nullable|string',
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $reference = 'DEP-' . strtoupper(bin2hex(random_bytes(4)));

        try {
            DB::transaction(function () use ($user, $amount, $reference, $request) {
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'deposit',
                    'status' => 'pending',
                    'reference' => $reference,
                ]);
            });

            return redirect()->route('wallet.recharger')->with('success', 'Demande de dépôt de ' . $amount . ' XAF soumise avec succès. En attente de validation.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Échec du dépôt: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle Withdrawal Request
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|in:1000,5000,15000,50000,150000,500000,1500000,5000000',
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
            DB::transaction(function () use ($user, $amount, $reference) {
                $user->balance -= $amount;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => -$amount,
                    'type' => 'withdrawal',
                    'status' => 'pending',
                    'reference' => $reference,
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
}
