<?php

namespace App\Http\Controllers;

use App\Models\VaultPlan;
use App\Models\VaultInvestment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VaultController extends Controller
{
    public function index(Request $request)
    {
        $vaults = VaultPlan::where('active', true)->get();
        
        $userInvestments = $request->user()->vaultInvestments()
            ->with('vaultPlan')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('VaultsMarket', [
            'vaults' => $vaults,
            'userInvestments' => $userInvestments,
        ]);
    }

    public function invest(Request $request, $id)
    {
        $vault = VaultPlan::findOrFail($id);
        $user = $request->user();

        if (!$vault->active) {
            return back()->withErrors(['error' => 'Ce vault n\'est plus disponible.']);
        }

        if ($user->balance < $vault->fixed_investment_amount) {
            return back()->withErrors(['error' => 'Solde insuffisant pour investir dans ce vault.']);
        }

        try {
            DB::beginTransaction();

            $user->decrement('balance', $vault->fixed_investment_amount);

            VaultInvestment::create([
                'user_id' => $user->id,
                'vault_plan_id' => $vault->id,
                'amount' => $vault->fixed_investment_amount,
                'return_amount' => $vault->fixed_return,
                'expires_at' => Carbon::now()->addDays($vault->duration),
                'status' => 'active'
            ]);

            $user->transactions()->create([
                'type' => 'purchase',
                'amount' => -$vault->fixed_investment_amount,
                'reference' => 'VAULT_INV_' . uniqid(),
                'status' => 'completed'
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Investissement dans le vault réussi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Une erreur s\'est produite lors de l\'investissement.']);
        }
    }
}
