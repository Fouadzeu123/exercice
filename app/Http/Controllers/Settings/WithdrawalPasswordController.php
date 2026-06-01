<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class WithdrawalPasswordController extends Controller
{
    /**
     * Show the withdrawal password configuration page.
     */
    public function edit()
    {
        $user = Auth::user();
        return Inertia::render('settings/WithdrawalPassword', [
            'hasPassword' => !is_null($user->withdrawal_password),
        ]);
    }

    /**
     * Store or update the withdrawal password.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'withdrawal_password' => 'required|string|min:4|max:12|confirmed',
        ];

        // If user already has a withdrawal password, they should optionally provide the current one for validation
        if ($user->withdrawal_password) {
            $rules['current_password'] = 'required|string';
        }

        $request->validate($rules);

        // Verify current password if configured
        if ($user->withdrawal_password && !Hash::check($request->current_password, $user->withdrawal_password)) {
            return redirect()->back()->withErrors([
                'current_password' => 'Le mot de passe de retrait actuel est incorrect.',
            ]);
        }

        $user->update([
            'withdrawal_password' => Hash::make($request->withdrawal_password),
        ]);

        return redirect()->back()->with('success', 'Mot de passe de retrait mis à jour avec succès.');
    }
}
