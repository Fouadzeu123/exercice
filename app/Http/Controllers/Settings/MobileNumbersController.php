<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MobileNumbersController extends Controller
{
    /**
     * Show the mobile numbers configuration page.
     */
    public function edit()
    {
        $user = Auth::user();
        $methods = WithdrawalMethod::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('settings/MobileNumbers', [
            'methods' => $methods,
        ]);
    }

    /**
     * Store a new withdrawal mobile number.
     */
    public function store(Request $request)
    {
        $request->validate([
            'operator' => 'required|string|in:mtn,orange',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:withdrawal_methods,phone',
        ], [
            'phone.unique' => 'Ce numéro de retrait est déjà configuré par un autre utilisateur.',
        ]);

        $user = Auth::user();

        // Check if this is the first withdrawal method
        $isFirst = !WithdrawalMethod::where('user_id', $user->id)->exists();

        WithdrawalMethod::create([
            'user_id' => $user->id,
            'operator' => $request->operator,
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'is_default' => $isFirst,
        ]);

        return redirect()->back()->with('success', 'Numéro de retrait configuré avec succès.');
    }

    /**
     * Mark a withdrawal mobile number as default.
     */
    public function makeDefault($id)
    {
        $user = Auth::user();

        // Verify ownership
        $method = WithdrawalMethod::where('user_id', $user->id)->findOrFail($id);

        // Reset all defaults for this user
        WithdrawalMethod::where('user_id', $user->id)->update(['is_default' => false]);

        // Set this one as default
        $method->update(['is_default' => true]);

        return redirect()->back()->with('success', 'Moyen de retrait par défaut mis à jour.');
    }

    /**
     * Delete a withdrawal mobile number.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        // Verify ownership
        $method = WithdrawalMethod::where('user_id', $user->id)->findOrFail($id);
        $wasDefault = $method->is_default;

        $method->delete();

        // If it was default, make another one default if possible
        if ($wasDefault) {
            $next = WithdrawalMethod::where('user_id', $user->id)->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return redirect()->back()->with('success', 'Moyen de retrait supprimé.');
    }
}
