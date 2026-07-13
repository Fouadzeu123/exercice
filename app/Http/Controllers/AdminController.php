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
        $users = User::with('referrer:id,phone')
            ->withCount('referrals')
            ->orderBy('created_at', 'desc')
            ->get();
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
            return back()->withErrors(['error' => 'Les dépôts sont approuvés automatiquement par la passerelle de paiement.']);
        }

        if ($transaction->type === 'withdrawal') {
            $publicKey = config('notchpay.public_key') ?? config('services.notchpay.public_key');
            $secretKey = config('notchpay.private_key') ?? config('services.notchpay.secret_key');

            // MODE SIMULATION
            $isSimulation = config('notchpay.sandbox', false);

            if ($isSimulation) {
                DB::transaction(function () use ($transaction) {
                    $lockedTrx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                    if ($lockedTrx && $lockedTrx->status === 'pending') {
                        $lockedTrx->status = 'completed';
                        $lockedTrx->save();
                    }
                });
                return back()->with('success', 'Retrait approuvé et simulé avec succès.');
            }

            if ($publicKey && $secretKey) {
                if ($transaction->payment_method !== 'usdt') {
                    try {
                        $notchPayService = app(NotchPayService::class);
                        
                        $withdrawalCountry = 'CM';
                        $phonePrefix = config('notchpay.country_phone_codes.' . $withdrawalCountry, '237');

                        $phone = trim($transaction->payment_phone);
                        $phone = preg_replace('/\s+/', '', $phone);
                        if (!str_starts_with($phone, '+')) {
                            $phone = '+' . $phonePrefix . ltrim($phone, '0');
                        }

                        $amountFCFA = (int)abs($transaction->amount);
                        $amountNetFCFA = (int) round($amountFCFA * 0.94); // 6% admin fee

                        $beneficiaryChannel = config('notchpay.beneficiary_channels.' . $withdrawalCountry, 'cm.mobile');

                        // 1. Create a Beneficiary first
                        $beneficiary = $notchPayService->createBeneficiary([
                            'channel' => $beneficiaryChannel,
                            'name' => $transaction->user->name ?? 'User ' . $transaction->user_id,
                            'account_number' => $phone,
                            'email' => $transaction->user->email ?? ($phone . '@armicm.com'),
                            'phone' => $phone,
                            'country' => strtolower($withdrawalCountry),
                        ]);

                        $beneficiaryId = $beneficiary->id ?? ($beneficiary->beneficiary->id ?? ($beneficiary->beneficiary ?? null));
                        if (!$beneficiaryId) {
                            return back()->withErrors(['error' => 'Échec de la création du bénéficiaire Notch Pay.']);
                        }

                        // 2. Initialize the Transfer
                        $notchPayService->initializeTransfer([
                            'amount' => $amountNetFCFA,
                            'currency' => 'XAF',
                            'beneficiary' => $beneficiaryId,
                            'reference' => $transaction->reference,
                            'description' => 'Retrait ARM HOLDING',
                        ]);

                    } catch (\Exception $e) {
                        $msg = $e->getMessage();
                        \Illuminate\Support\Facades\Log::error('Notch Pay transfer error: ' . $msg);
                        return back()->withErrors(['error' => 'Erreur de transfert Notch Pay : ' . $msg]);
                    }
                }
            }
        }

        DB::transaction(function () use ($transaction) {
            $lockedTrx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
            if ($lockedTrx && $lockedTrx->status === 'pending') {
                $lockedTrx->status = 'completed';
                $lockedTrx->save();

                // If it is a deposit, increment the user's balance
                if ($lockedTrx->type === 'deposit') {
                    $user = User::where('id', $lockedTrx->user_id)->lockForUpdate()->first();
                    if ($user) {
                        $user->balance += $lockedTrx->amount;
                        $user->save();
                    }
                }
            }
        });

        return back()->with('success', 'Transaction approuvée et validée avec succès.');
    }

    /**
     * Reject a pending transaction and revert balance if needed.
     */
    public function rejectTransaction(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $lockedTrx = Transaction::where('id', $id)->lockForUpdate()->firstOrFail();
                if ($lockedTrx->status !== 'pending') {
                    throw new \Exception('La transaction n\'est plus en attente.');
                }

                // Refund user balance for withdrawal
                if ($lockedTrx->type === 'withdrawal') {
                    $user = User::where('id', $lockedTrx->user_id)->lockForUpdate()->first();
                    if ($user) {
                        $user->balance += abs($lockedTrx->amount);
                        $user->save();
                    }
                }
                $lockedTrx->status = 'rejected';
                $lockedTrx->save();
            });

            return back()->with('success', 'Transaction rejetée et capitaux restitués au mineur.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update a user's variables: balance, role, vip levels, draws, rigging.
     */
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'balance' => 'required|numeric',
            'role' => 'required|string|in:user,admin',
            'vip_level' => 'required|integer|min:0|max:5',
            'avip_level' => 'required|integer|min:0|max:3',
            'draw_spins' => 'required|integer|min:0',
            'next_spin_prize_index' => 'nullable|integer|min:0|max:6',
            'password' => 'nullable|string|min:6',
            'withdrawal_password' => 'nullable|string|min:4|max:12',
            'is_generation_blocked' => 'required|boolean',
        ]);

        \DB::transaction(function () use ($id, $request) {
            $lockedUser = User::where('id', $id)->lockForUpdate()->firstOrFail();
            $oldBalance = (float) $lockedUser->balance;
            $newBalance = (float) $request->balance;
            $diff = $newBalance - $oldBalance;

            $updateData = [
                'balance' => $request->balance,
                'role' => $request->role,
                'vip_level' => $request->vip_level,
                'avip_level' => $request->avip_level,
                'draw_spins' => $request->draw_spins,
                'next_spin_prize_index' => $request->next_spin_prize_index,
                'is_generation_blocked' => (bool) $request->is_generation_blocked,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
            }

            if ($request->filled('withdrawal_password')) {
                $updateData['withdrawal_password'] = \Illuminate\Support\Facades\Hash::make($request->withdrawal_password);
            }

            $lockedUser->update($updateData);

            if ($diff != 0) {
                \App\Models\Transaction::create([
                    'user_id' => $lockedUser->id,
                    'amount' => $diff,
                    'type' => $diff > 0 ? 'deposit' : 'withdrawal',
                    'status' => 'completed',
                    'reference' => 'ADMIN_ADJ_' . strtoupper(bin2hex(random_bytes(3))),
                ]);
            }
        });

        return back()->with('success', 'Profil et variables du mineur mis à jour avec succès.');
    }

    /**
     * Delete/Ban a user from the ecosystem.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas vous bannir vous-même.']);
        }

        $user->delete();
        return back()->with('success', 'Utilisateur supprimé et révoqué de l\'infrastructure.');
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

        return back()->with('success', 'Code cadeau promotionnel généré avec succès.');
    }

    /**
     * Delete a gift code.
     */
    public function deleteGiftCode($id)
    {
        $code = GiftCode::findOrFail($id);
        $code->delete();
        return back()->with('success', 'Code cadeau supprimé et désactivé.');
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
            $imageUrl = url('/uploads/' . $fileName);
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

        return back()->with('success', 'Accélérateur AVIP configuré et mis à jour.');
    }

    /**
     * Soft delete an AVIP product.
     */
    public function deleteAvipProduct($id)
    {
        $product = AVIPProduct::findOrFail($id);
        $product->delete(); // Soft deletes it
        return back()->with('success', 'Accélérateur AVIP supprimé logiquement (offres masquées, commandes actives préservées).');
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
            $imageUrl = url('/uploads/' . $fileName);
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

        return back()->with('success', 'Nouveau nœud de serveur standard créé avec succès.');
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

        return back()->with('success', 'Nouvel équipement AVIP créé avec succès.');
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
            $imageUrl = url('/uploads/' . $fileName);
        }

        \App\Models\Announcement::create([
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $imageUrl,
            'link' => $request->link,
            'active' => $request->active,
        ]);

        return back()->with('success', 'Nouvelle annonce créée et publiée avec succès.');
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
            $imageUrl = url('/uploads/' . $fileName);
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
            'block_generation_global' => 'required|boolean',
        ]);

        \App\Services\SettingsService::setMultiple([
            'min_deposit' => (float) $request->min_deposit,
            'min_withdrawal' => (float) $request->min_withdrawal,
            'support_telegram' => $request->support_telegram,
            'support_whatsapp' => $request->support_whatsapp,
            'lucky_draw_cost' => (int) $request->lucky_draw_cost,
            'generation_duration' => (int) $request->generation_duration,
            'vip_salaries' => array_map('floatval', $request->vip_salaries),
            'block_generation_global' => (bool) $request->block_generation_global,
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
            $imageUrl = url('/uploads/' . $fileName);
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
            $imageUrl = url('/uploads/' . $fileName);
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
     * Inspect individual user details, affiliations, active investments, and transactions history.
     */
    public function getUserDetails($id)
    {
        $user = User::with([
            'referrer:id,phone',
            'referrals:id,phone,vip_level,avip_level,referrer_id,created_at',
            'referrals.userNodes' => function ($q) {
                $q->where('active', true)->where(function ($sq) {
                    $sq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->with('node:id,name');
            },
            'referrals.userAVIPProducts' => function ($q) {
                $q->where('active', true)->where(function ($sq) {
                    $sq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->with('avipProduct:id,name');
            },
            'referrals.vaultInvestments' => function ($q) {
                $q->where('status', 'active')->with('vaultPlan:id,name');
            }
        ])->findOrFail($id);

        // Recalculate VIP/AVIP status dynamically on request to guarantee accuracy
        $user->recalculateVipAndAvipStatus();

        // Refresh user model after recalculation
        $user->refresh();

        // Fetch active standard nodes
        $activeNodes = \App\Models\UserNode::with('node')
            ->where('user_id', $id)
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->get();

        // Fetch active AVIP products
        $activeAvips = \App\Models\UserAVIPProduct::with('avipProduct')
            ->where('user_id', $id)
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->get();

        // Fetch active Vault plans placements
        $activeVaults = \App\Models\VaultInvestment::with('vaultPlan')
            ->where('user_id', $id)
            ->where('status', 'active')
            ->get();

        // Fetch complete transaction history
        $transactions = Transaction::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate affiliate statistics using DB optimization
        $teamStats = DB::table('users as referrals')
            ->leftJoin('user_nodes', function ($join) {
                $join->on('referrals.id', '=', 'user_nodes.user_id')
                     ->where('user_nodes.active', true)
                     ->where(function ($q) {
                         $q->whereNull('user_nodes.expires_at')
                           ->orWhere('user_nodes.expires_at', '>', now());
                     });
            })
            ->leftJoin('user_avip_products', function ($join) {
                $join->on('referrals.id', '=', 'user_avip_products.user_id')
                     ->where('user_avip_products.active', true)
                     ->where(function ($q) {
                         $q->whereNull('user_avip_products.expires_at')
                           ->orWhere('user_avip_products.expires_at', '>', now());
                     });
            })
            ->leftJoin('vault_investments', function ($join) {
                $join->on('referrals.id', '=', 'vault_investments.user_id')
                     ->where('vault_investments.status', 'active');
            })
            ->leftJoin('transactions', function ($join) {
                $join->on('referrals.id', '=', 'transactions.user_id')
                     ->where('transactions.type', 'purchase')
                     ->where('transactions.amount', '<', 0)
                     ->where('transactions.status', 'completed');
            })
            ->where('referrals.referrer_id', $id)
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN user_nodes.id IS NOT NULL OR user_avip_products.id IS NOT NULL OR vault_investments.id IS NOT NULL THEN referrals.id END) as active_referrals,
                COALESCE(SUM(ABS(transactions.amount)), 0) as team_volume
            ')
            ->first();

        // Sum personal purchases
        $personalInvested = abs(Transaction::where('user_id', $id)
            ->where('type', 'purchase')
            ->where('amount', '<', 0)
            ->where('status', 'completed')
            ->sum('amount'));

        // Prepare referrals with formatted active investments list
        $formattedReferrals = $user->referrals->map(function ($ref) {
            return [
                'id' => $ref->id,
                'phone' => $ref->phone,
                'vip_level' => $ref->vip_level,
                'avip_level' => $ref->avip_level,
                'created_at' => $ref->created_at,
                'active_nodes' => $ref->userNodes->map(function ($un) {
                    return $un->node->name ?? 'GPU';
                })->toArray(),
                'active_avips' => $ref->userAVIPProducts->map(function ($ua) {
                    return $ua->avipProduct->name ?? 'AVIP';
                })->toArray(),
                'active_vaults' => $ref->vaultInvestments->map(function ($vi) {
                    return $vi->vaultPlan->name ?? 'Vault';
                })->toArray(),
            ];
        });

        return response()->json([
            'user' => $user,
            'referrals' => $formattedReferrals,
            'active_nodes' => $activeNodes,
            'active_avips' => $activeAvips,
            'active_vaults' => $activeVaults,
            'transactions' => $transactions,
            'stats' => [
                'active_referrals' => (int) $teamStats->active_referrals,
                'team_volume' => (float) $teamStats->team_volume,
                'personal_invested' => $personalInvested,
            ]
        ]);
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

