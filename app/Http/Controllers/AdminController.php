<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Node;
use App\Models\AVIPProduct;
use App\Models\GiftCode;
use App\Models\VaultPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use DB;
use App\Services\NotchPayService;

class AdminController extends Controller
{


    /**
     * Admin dashboard home with stats, transactions, users, giftcodes, and catalog products.
     */
    public function index()
    {
        $pendingTransactions = Transaction::with('user')
            ->where('status', 'pending')
            ->where('type', 'withdrawal')
            ->orderBy('created_at', 'desc')
            ->get();
        $users = User::orderBy('created_at', 'desc')->get();
        $giftCodes = GiftCode::orderBy('created_at', 'desc')->get();

        // Fetch all nodes and AVIP products, including soft-deleted ones so admins can configure or restore them
        $nodes = Node::withTrashed()->orderBy('created_at', 'desc')->get();
        $avipProducts = AVIPProduct::withTrashed()->orderBy('created_at', 'desc')->get();
        $vaultPlans = VaultPlan::orderBy('created_at', 'desc')->get();

        // Calculate metrics
        $totalDeposits = Transaction::where('type', 'deposit')->where('status', 'completed')->sum('amount');
        $totalWithdrawals = abs(Transaction::where('type', 'withdrawal')->where('status', 'completed')->sum('amount'));
        $activeNodesCount = \App\Models\UserNode::where('active', true)->count();

        $announcements = \App\Models\Announcement::orderBy('created_at', 'desc')->get();
        $settings = \App\Services\SettingsService::all();

        return Inertia::render('admin/AdminDashboard', [
            'pendingTransactions' => $pendingTransactions,
            'users' => $users,
            'giftCodes' => $giftCodes,
            'nodes' => $nodes,
            'avipProducts' => $avipProducts,
            'vaultPlans' => $vaultPlans,
            'announcements' => $announcements,
            'settings' => $settings,
            'metrics' => [
                'total_deposits' => (float)$totalDeposits,
                'total_withdrawals' => (float)$totalWithdrawals,
                'active_nodes_count' => (int)$activeNodesCount,
                'total_users_count' => (int)$users->count(),
            ]
        ]);
    }

    /**
     * Approve a pending transaction (e.g., withdrawal, deposit).
     */
    public function approveTransaction(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'La transaction n\'est plus en attente.']);
        }

        if ($transaction->type === 'deposit') {
            return back()->withErrors(['error' => 'Les dÃ©pÃ´ts sont approuvÃ©s automatiquement par la passerelle de paiement.']);
        }

        if ($transaction->type === 'withdrawal') {
            $publicKey = config('services.notchpay.public_key');
            $secretKey = config('services.notchpay.secret_key');

            if ($publicKey && $secretKey) {
                if ($transaction->payment_method !== 'usdt') {
                    try {
                        $notchPayService = app(NotchPayService::class);
                        $channel = $transaction->payment_method === 'orange' ? 'cm.orange' : 'cm.mtn';

                        // 1. Create a Beneficiary first
                        $beneficiary = $notchPayService->createBeneficiary([
                            'channel' => $channel,
                            'name' => $transaction->user->name ?? 'User ' . $transaction->user_id,
                            'account_number' => $transaction->payment_phone,
                            'email' => $transaction->user->email ?? ($transaction->payment_phone . '@armicm.com'),
                            'phone' => $transaction->payment_phone,
                            'country' => 'CM',
                        ]);

                        $beneficiaryId = $beneficiary->id ?? null;
                        if (!$beneficiaryId) {
                            return back()->withErrors(['error' => 'Ã‰chec de la crÃ©ation du bÃ©nÃ©ficiaire Notch Pay.']);
                        }

                        // 2. Initialize the Transfer
                        $notchPayService->initializeTransfer([
                            'amount' => (int)abs($transaction->amount),
                            'currency' => 'XAF',
                            'beneficiary' => $beneficiaryId,
                            'reference' => $transaction->reference,
                            'description' => 'Retrait ARM HOLDING',
                        ]);

                    } catch (\Exception $e) {
                        return back()->withErrors(['error' => 'Erreur de transfert Notch Pay : ' . $e->getMessage()]);
                    }
                }
            }
        }

        DB::transaction(function () use ($transaction) {
            $transaction->status = 'completed';
            $transaction->save();

            // If it is a deposit, increment the user's balance
            if ($transaction->type === 'deposit') {
                $user = $transaction->user;
                $user->balance += $transaction->amount;
                $user->save();
            }
        });

        return back()->with('success', 'Transaction approuvÃ©e et validÃ©e avec succÃ¨s.');
    }

    /**
     * Reject a pending transaction and revert balance if needed.
     */
    public function rejectTransaction(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'La transaction n\'est plus en attente.']);
        }

        DB::transaction(function () use ($transaction) {
            // Refund user balance for withdrawal
            if ($transaction->type === 'withdrawal') {
                $user = $transaction->user;
                $user->balance += abs($transaction->amount);
                $user->save();
            }
            $transaction->status = 'rejected';
            $transaction->save();
        });

        return back()->with('success', 'Transaction rejetÃ©e et capitaux restituÃ©s au mineur.');
    }

    /**
     * Update a user's variables: balance, role, vip levels, draws, rigging.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'balance' => 'required|numeric',
            'role' => 'required|string|in:user,admin',
            'vip_level' => 'required|integer|min:0|max:5',
            'avip_level' => 'required|integer|min:0|max:3',
            'draw_spins' => 'required|integer|min:0',
            'next_spin_prize_index' => 'nullable|integer|min:0|max:6',
        ]);

        $oldBalance = (float) $user->balance;
        $newBalance = (float) $request->balance;
        $diff = $newBalance - $oldBalance;

        \DB::transaction(function () use ($user, $request, $diff) {
            $user->update([
                'balance' => $request->balance,
                'role' => $request->role,
                'vip_level' => $request->vip_level,
                'avip_level' => $request->avip_level,
                'draw_spins' => $request->draw_spins,
                'next_spin_prize_index' => $request->next_spin_prize_index,
            ]);

            if ($diff != 0) {
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $diff,
                    'type' => $diff > 0 ? 'deposit' : 'withdrawal',
                    'status' => 'completed',
                    'reference' => 'ADMIN_ADJ_' . strtoupper(bin2hex(random_bytes(3))),
                ]);
            }
        });

        return back()->with('success', 'Profil et variables du mineur mis Ã  jour avec succÃ¨s.');
    }

    /**
     * Delete/Ban a user from the ecosystem.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas vous bannir vous-mÃªme.']);
        }

        $user->delete();
        return back()->with('success', 'Utilisateur supprimÃ© et rÃ©voquÃ© de l\'infrastructure.');
    }

    /**
     * Create a new dynamic gift code.
     */
    public function createGiftCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:gift_codes,code',
            'amount' => 'required|numeric|min:1',
            'max_usages' => 'required|integer|min:1',
        ]);

        GiftCode::create([
            'code' => strtoupper(trim($request->code)),
            'amount' => $request->amount,
            'max_usages' => $request->max_usages,
            'usages' => 0,
        ]);

        return back()->with('success', 'Code cadeau promotionnel gÃ©nÃ©rÃ© avec succÃ¨s.');
    }

    /**
     * Delete a gift code.
     */
    public function deleteGiftCode($id)
    {
        $code = GiftCode::findOrFail($id);
        $code->delete();
        return back()->with('success', 'Code cadeau supprimÃ© et dÃ©sactivÃ©.');
    }

    /**
     * Configure / Update standard node details.
     */
    public function updateNode(Request $request, $id)
    {
        $node = Node::withTrashed()->findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'generation_profit' => 'required|numeric|min:0',
            'referral_reward' => 'nullable|numeric|min:0',
            'technology_level' => 'required|integer|min:0|max:5', // VIP requis
            'duration' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'limited_purchase_count' => 'nullable|integer|min:0',
            'active' => 'required|boolean',
            'is_limited' => 'nullable|boolean',
            'required_active_referrals' => 'nullable|integer|min:0',
            'restore' => 'nullable|boolean', // Option to restore if soft deleted
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $imageUrl = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        $node->update([
            'name' => $request->name,
            'amount' => $request->amount,
            'generation_profit' => $request->generation_profit,
            'referral_reward' => $request->referral_reward ?? 0.00,
            'technology_level' => $request->technology_level,
            'duration' => $request->duration,
            'stock_quantity' => $request->stock_quantity,
            'limited_purchase_count' => $request->limited_purchase_count,
            'active' => $request->active,
            'is_limited' => $request->is_limited ? true : false,
            'required_active_referrals' => $request->required_active_referrals ?? 0,
            'image' => $imageUrl,
        ]);

        if ($request->restore && $node->trashed()) {
            $node->restore();
        }

        return back()->with('success', 'NÅ“ud serveur configurÃ© et mis Ã  jour.');
    }

    /**
     * Soft delete a standard node.
     */
    public function deleteNode($id)
    {
        $node = Node::findOrFail($id);
        $node->delete(); // Soft deletes it
        return back()->with('success', 'NÅ“ud serveur supprimÃ© logiquement (offres masquÃ©es, commandes actives prÃ©servÃ©es).');
    }

    /**
     * Configure / Update AVIP product details.
     */
    public function updateAvipProduct(Request $request, $id)
    {
        $product = AVIPProduct::withTrashed()->findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'daily_salary' => 'required|numeric|min:0',
            'referral_reward' => 'nullable|numeric|min:0',
            'required_vip_level' => 'required|integer|min:1|max:5',
            'avip_level' => 'required|integer|min:1|max:5',
            'duration' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'limited_purchase_count' => 'nullable|integer|min:0',
            'active' => 'required|boolean',
            'is_limited' => 'nullable|boolean',
            'required_active_referrals' => 'nullable|integer|min:0',
            'restore' => 'nullable|boolean',
            'image' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $image = $request->image;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $image = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'amount' => $request->amount,
            'daily_salary' => $request->daily_salary,
            'referral_reward' => $request->referral_reward ?? 0.00,
            'required_vip_level' => $request->required_vip_level,
            'avip_level' => $request->avip_level,
            'duration' => $request->duration,
            'stock_quantity' => $request->stock_quantity,
            'limited_purchase_count' => $request->limited_purchase_count,
            'active' => $request->active,
            'is_limited' => $request->is_limited ? true : false,
            'required_active_referrals' => $request->required_active_referrals ?? 0,
            'image' => $image,
        ]);

        if ($request->restore && $product->trashed()) {
            $product->restore();
        }

        return back()->with('success', 'AccÃ©lÃ©rateur AVIP configurÃ© et mis Ã  jour.');
    }

    /**
     * Soft delete an AVIP product.
     */
    public function deleteAvipProduct($id)
    {
        $product = AVIPProduct::findOrFail($id);
        $product->delete(); // Soft deletes it
        return back()->with('success', 'AccÃ©lÃ©rateur AVIP supprimÃ© logiquement (offres masquÃ©es, commandes actives prÃ©servÃ©es).');
    }

    /**
     * Create a new standard node.
     */
    public function createNode(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'generation_profit' => 'required|numeric|min:0',
            'referral_reward' => 'nullable|numeric|min:0',
            'technology_level' => 'required|integer|min:0|max:5',
            'duration' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'limited_purchase_count' => 'nullable|integer|min:0',
            'active' => 'required|boolean',
            'is_limited' => 'nullable|boolean',
            'required_active_referrals' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $imageUrl = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        Node::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'generation_profit' => $request->generation_profit,
            'referral_reward' => $request->referral_reward ?? 0.00,
            'technology_level' => $request->technology_level,
            'duration' => $request->duration,
            'stock_quantity' => $request->stock_quantity,
            'limited_purchase_count' => $request->limited_purchase_count,
            'active' => $request->active,
            'is_limited' => $request->is_limited ? true : false,
            'required_active_referrals' => $request->required_active_referrals ?? 0,
            'image' => $imageUrl,
        ]);

        return back()->with('success', 'Nouveau nÅ“ud de serveur standard crÃ©Ã© avec succÃ¨s.');
    }

    /**
     * Create a new AVIP product.
     */
    public function createAvipProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'daily_salary' => 'required|numeric|min:0',
            'referral_reward' => 'nullable|numeric|min:0',
            'required_vip_level' => 'required|integer|min:1|max:5',
            'avip_level' => 'required|integer|min:1|max:5',
            'duration' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'limited_purchase_count' => 'nullable|integer|min:0',
            'active' => 'required|boolean',
            'is_limited' => 'nullable|boolean',
            'required_active_referrals' => 'nullable|integer|min:0',
            'image' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $image = $request->image ?? 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=600&q=80';
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $image = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        AVIPProduct::create([
            'name' => $request->name,
            'description' => $request->description ?? '',
            'amount' => $request->amount,
            'daily_salary' => $request->daily_salary,
            'referral_reward' => $request->referral_reward ?? 0.00,
            'required_vip_level' => $request->required_vip_level,
            'avip_level' => $request->avip_level,
            'duration' => $request->duration,
            'stock_quantity' => $request->stock_quantity,
            'limited_purchase_count' => $request->limited_purchase_count,
            'active' => $request->active,
            'is_limited' => $request->is_limited ? true : false,
            'required_active_referrals' => $request->required_active_referrals ?? 0,
            'image' => $image,
        ]);

        return back()->with('success', 'Nouvel Ã©quipement AVIP crÃ©Ã© avec succÃ¨s.');
    }

    /**
     * Create a new announcement.
     */
    public function createAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
            'link' => 'nullable|string',
            'active' => 'required|boolean',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $imageUrl = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        \App\Models\Announcement::create([
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $imageUrl,
            'link' => $request->link,
            'active' => $request->active,
        ]);

        return back()->with('success', 'Nouvelle annonce crÃ©Ã©e et publiÃ©e avec succÃ¨s.');
    }

    /**
     * Update an announcement.
     */
    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
            'link' => 'nullable|string',
            'active' => 'required|boolean',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $imageUrl = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        $announcement->update([
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $imageUrl,
            'link' => $request->link,
            'active' => $request->active,
        ]);

        return back()->with('success', 'Annonce mise à jour avec succès.');
    }

    /**
     * Delete an announcement.
     */
    public function deleteAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $announcement->delete();

        return back()->with('success', 'Annonce supprimée avec succès.');
    }

    /**
     * Update global system settings and VIP salaries.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'min_deposit' => 'required|numeric|min:0',
            'min_withdrawal' => 'required|numeric|min:0',
            'support_telegram' => 'nullable|string',
            'support_whatsapp' => 'nullable|string',
            'lucky_draw_cost' => 'required|integer|min:0',
            'generation_duration' => 'required|integer|min:1',
            'vip_salaries' => 'required|array',
            'vip_salaries.0' => 'required|numeric|min:0',
            'vip_salaries.1' => 'required|numeric|min:0',
            'vip_salaries.2' => 'required|numeric|min:0',
            'vip_salaries.3' => 'required|numeric|min:0',
            'vip_salaries.4' => 'required|numeric|min:0',
            'vip_salaries.5' => 'required|numeric|min:0',
        ]);

        \App\Services\SettingsService::setMultiple([
            'min_deposit' => (float) $request->min_deposit,
            'min_withdrawal' => (float) $request->min_withdrawal,
            'support_telegram' => $request->support_telegram,
            'support_whatsapp' => $request->support_whatsapp,
            'lucky_draw_cost' => (int) $request->lucky_draw_cost,
            'generation_duration' => (int) $request->generation_duration,
            'vip_salaries' => array_map('floatval', $request->vip_salaries),
        ]);

        return back()->with('success', 'Configuration globale du système enregistrée.');
    }

    /**
     * Create a new vault plan.
     */
    public function createVaultPlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'fixed_investment_amount' => 'required|numeric|min:0',
            'fixed_return' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'payout_type' => 'required|string|in:daily,on_expiration',
            'active' => 'required|boolean',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $imageUrl = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        $profitAmount = $request->fixed_return - $request->fixed_investment_amount;

        VaultPlan::create([
            'name' => $request->name,
            'fixed_investment_amount' => $request->fixed_investment_amount,
            'fixed_return' => $request->fixed_return,
            'profit_amount' => $profitAmount,
            'duration' => $request->duration,
            'payout_type' => $request->payout_type,
            'active' => $request->active,
            'image' => $imageUrl,
        ]);

        return back()->with('success', 'Nouveau produit de coffre-fort (Vault Plan) créé avec succès.');
    }

    /**
     * Update an existing vault plan.
     */
    public function updateVaultPlan(Request $request, $id)
    {
        $vaultPlan = VaultPlan::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'fixed_investment_amount' => 'required|numeric|min:0',
            'fixed_return' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'payout_type' => 'required|string|in:daily,on_expiration',
            'active' => 'required|boolean',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $fileName);
            $imageUrl = $this->forceHttpsUrl('/uploads/' . $fileName);
        }

        $profitAmount = $request->fixed_return - $request->fixed_investment_amount;

        $vaultPlan->update([
            'name' => $request->name,
            'fixed_investment_amount' => $request->fixed_investment_amount,
            'fixed_return' => $request->fixed_return,
            'profit_amount' => $profitAmount,
            'duration' => $request->duration,
            'payout_type' => $request->payout_type,
            'active' => $request->active,
            'image' => $imageUrl ?? $vaultPlan->image,
        ]);

        return back()->with('success', 'Produit de coffre-fort (Vault Plan) mis Ã  jour.');
    }

    /**
     * Delete a vault plan.
     */
    public function deleteVaultPlan($id)
    {
        $vaultPlan = VaultPlan::findOrFail($id);
        $vaultPlan->delete();
        return back()->with('success', 'Produit de coffre-fort (Vault Plan) supprimé avec succès.');
    }

    /**
     * Force absolute URL to be HTTPS for mobile webview compatibility.
     */
    private function forceHttpsUrl($path)
    {
        return str_replace('http://', 'https://', url($path));
    }
}
?>

