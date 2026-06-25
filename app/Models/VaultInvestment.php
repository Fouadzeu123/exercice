<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VaultInvestment extends Model
{
    protected $guarded = [];

    /**
     * Les attributs qui doivent être convertis.
     */
    protected $casts = [
        'amount' => 'float',
        'return_amount' => 'float',
        'expires_at' => 'datetime',
    ];

    // --- RELATIONS ---

    /**
     * L'investissement appartient à un utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'investissement correspond à un plan de vault précis.
     */
    public function vaultPlan(): BelongsTo
    {
        return $this->belongsTo(VaultPlan::class);
    }

    // --- SCOPES & METHODES ---

    /**
     * Filtre : Uniquement les investissements actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Vérifie si le vault est expiré.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Process pending daily or expiration payouts for a user.
     */
    public static function processUserPayouts($user)
    {
        if (!$user) {
            return;
        }

        $activeInvestments = self::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('vaultPlan')
            ->get();

        foreach ($activeInvestments as $investment) {
            $plan = $investment->vaultPlan;
            if (!$plan) {
                continue;
            }

            if ($plan->payout_type === 'daily') {
                // Calculate elapsed days since creation
                $createdAt = Carbon::parse($investment->created_at);
                $now = Carbon::now();
                
                // Determine how many 24-hour periods have elapsed
                $elapsedDays = (int) floor($createdAt->diffInHours($now) / 24);
                
                // Cap elapsed days to the duration of the plan
                $elapsedDays = min($plan->duration, $elapsedDays);
                
                // Days due for payout
                $dueDays = $elapsedDays - $investment->payouts_claimed;
                
                if ($dueDays > 0) {
                    $dailyPayout = $investment->return_amount / $plan->duration;
                    $payoutAmount = $dailyPayout * $dueDays;

                    \DB::transaction(function () use ($user, $investment, $dueDays, $payoutAmount, $plan) {
                        $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                        $lockedInvestment = self::where('id', $investment->id)->lockForUpdate()->firstOrFail();

                        // Re-evaluate due days on locked record to prevent double-claiming
                        $createdAt = Carbon::parse($lockedInvestment->created_at);
                        $now = Carbon::now();
                        $elapsedDays = (int) floor($createdAt->diffInHours($now) / 24);
                        $elapsedDays = min($plan->duration, $elapsedDays);
                        $currentDueDays = $elapsedDays - $lockedInvestment->payouts_claimed;

                        if ($currentDueDays <= 0) {
                            return;
                        }

                        $dailyPayout = $lockedInvestment->return_amount / $plan->duration;
                        $currentPayoutAmount = $dailyPayout * $currentDueDays;

                        // Credit user balance
                        $lockedUser->balance += $currentPayoutAmount;
                        $lockedUser->save();

                        // Update investment payouts count
                        $lockedInvestment->payouts_claimed += $currentDueDays;
                        $lockedInvestment->last_payout_at = Carbon::now();
                        
                        if ($lockedInvestment->payouts_claimed >= $plan->duration) {
                            $lockedInvestment->status = 'completed';
                        }
                        $lockedInvestment->save();

                        // Log transaction
                        Transaction::create([
                            'user_id' => $lockedUser->id,
                            'amount' => $currentPayoutAmount,
                            'type' => 'earnings',
                            'status' => 'completed',
                            'reference' => 'VLT-D-' . $lockedInvestment->id . '-' . strtoupper(bin2hex(random_bytes(3))),
                        ]);

                        // Distribute daily referral commissions (L1 = 5%, L2 = 2%, L3 = 1%)
                        $lockedUser->payDailyCommissions($currentPayoutAmount);

                        // Sync back to memory user object
                        $user->balance = $lockedUser->balance;
                    });
                }
            } else {
                // payout_type === 'on_expiration'
                $now = Carbon::now();
                if ($now->isAfter($investment->expires_at)) {
                    \DB::transaction(function () use ($user, $investment) {
                        $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                        $lockedInvestment = self::where('id', $investment->id)->lockForUpdate()->firstOrFail();

                        if ($lockedInvestment->status !== 'active') {
                            return;
                        }

                        // Credit user balance
                        $lockedUser->balance += $lockedInvestment->return_amount;
                        $lockedUser->save();

                        // Update investment state
                        $lockedInvestment->status = 'completed';
                        $lockedInvestment->last_payout_at = Carbon::now();
                        $lockedInvestment->save();

                        // Log transaction
                        Transaction::create([
                            'user_id' => $lockedUser->id,
                            'amount' => $lockedInvestment->return_amount,
                            'type' => 'earnings',
                            'status' => 'completed',
                            'reference' => 'VLT-E-' . $lockedInvestment->id . '-' . strtoupper(bin2hex(random_bytes(3))),
                        ]);

                        // Sync back to memory user object
                        $user->balance = $lockedUser->balance;
                    });
                }
            }
        }
    }
}