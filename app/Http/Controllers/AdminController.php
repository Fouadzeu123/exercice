<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Node;
use App\Models\AVIPProduct;
use App\Models\GiftCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use DB;

class AdminController extends Controller
{


    /**
     * Admin dashboard home with stats, transactions, users, giftcodes, and catalog products.
     */
    public function index()
    {
        $pendingTransactions = Transaction::with('user')->where('status', 'pending')->orderBy('created_at', 'desc')->get();
        $users = User::orderBy('created_at', 'desc')->get();
        $giftCodes = GiftCode::orderBy('created_at', 'desc')->get();
        
        // Fetch all nodes and AVIP products, including soft-deleted ones so admins can configure or restore them
        $nodes = Node::withTrashed()->orderBy('created_at', 'desc')->get();
        $avipProducts = AVIPProduct::withTrashed()->orderBy('created_at', 'desc')->get();

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
        
        return back()->with('success', 'Transaction approuvée et validée avec succès.');
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
        
        return back()->with('success', 'Transaction rejetée et capitaux restitués au mineur.');
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
            'technology_level' => 'required|integer|min:0|max:5', // VIP requis
            'duration' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'limited_purchase_count' => 'nullable|integer|min:0',
            'active' => 'required|boolean',
            'restore' => 'nullable|boolean', // Option to restore if soft deleted
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('images'), $fileName);
            $imageUrl = '/images/' . $fileName;
        }

        $node->update([
            'name' => $request->name,
            'amount' => $request->amount,
            'generation_profit' => $request->generation_profit,
            'technology_level' => $request->technology_level,
            'duration' => $request->duration,
            'stock_quantity' => $request->stock_quantity,
            'limited_purchase_count' => $request->limited_purchase_count,
            'active' => $request->active,
            'image_url' => $imageUrl,
        ]);

        if ($request->restore && $node->trashed()) {
            $node->restore();
        }

        return back()->with('success', 'Nœud serveur configuré et mis à jour.');
    }

    /**
     * Soft delete a standard node.
     */
    public function deleteNode($id)
    {
        $node = Node::findOrFail($id);
        $node->delete(); // Soft deletes it
        return back()->with('success', 'Nœud serveur supprimé logiquement (offres masquées, commandes actives préservées).');
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
            'required_vip_level' => 'required|integer|min:0|max:5',
            'avip_level' => 'required|integer|min:0|max:3',
            'active' => 'required|boolean',
            'restore' => 'nullable|boolean',
            'image' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $image = $request->image;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('images'), $fileName);
            $image = '/images/' . $fileName;
        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'amount' => $request->amount,
            'daily_salary' => $request->daily_salary,
            'required_vip_level' => $request->required_vip_level,
            'avip_level' => $request->avip_level,
            'active' => $request->active,
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
            'technology_level' => 'required|integer|min:0|max:5',
            'duration' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'limited_purchase_count' => 'nullable|integer|min:0',
            'active' => 'required|boolean',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('images'), $fileName);
            $imageUrl = '/images/' . $fileName;
        }

        Node::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'generation_profit' => $request->generation_profit,
            'technology_level' => $request->technology_level,
            'duration' => $request->duration,
            'stock_quantity' => $request->stock_quantity,
            'limited_purchase_count' => $request->limited_purchase_count,
            'active' => $request->active,
            'image_url' => $imageUrl,
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
            'required_vip_level' => 'required|integer|min:0|max:5',
            'avip_level' => 'required|integer|min:0|max:3',
            'active' => 'required|boolean',
            'image' => 'nullable|string',
            'image_file' => 'nullable|file|image|max:5120',
        ]);

        $image = $request->image ?? 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=600&q=80';
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('images'), $fileName);
            $image = '/images/' . $fileName;
        }

        AVIPProduct::create([
            'name' => $request->name,
            'description' => $request->description ?? '',
            'amount' => $request->amount,
            'daily_salary' => $request->daily_salary,
            'required_vip_level' => $request->required_vip_level,
            'avip_level' => $request->avip_level,
            'active' => $request->active,
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
            $file->move(public_path('images'), $fileName);
            $imageUrl = '/images/' . $fileName;
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
            $file->move(public_path('images'), $fileName);
            $imageUrl = '/images/' . $fileName;
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
}
?>
