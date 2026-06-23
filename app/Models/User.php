<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Support\Facades\DB;

#[Fillable(['password', 'withdrawal_password', 'phone', 'balance', 'referral_code', 'referrer_id', 'vip_level', 'avip_level', 'active_node_id', 'role', 'last_salary_claim_date', 'draw_spins', 'next_spin_prize_index'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'balance' => 'float', // IMPORTANT : Assure que le solde est toujours un nombre
            'vip_level' => 'integer',
            'avip_level' => 'integer',
        ];
    }

    // --- RELATIONS ---

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function vaultInvestments(): HasMany
    {
        return $this->hasMany(VaultInvestment::class);
    }

    public function userNodes(): HasMany
    {
        return $this->hasMany(UserNode::class);
    }

    public function withdrawalMethods(): HasMany
    {
        return $this->hasMany(WithdrawalMethod::class);
    }

    public function userAVIPProducts(): HasMany
    {
        return $this->hasMany(UserAVIPProduct::class);
    }

    // --- METHODES METIER OPTIMISEES ---

    /**
     * Scope: Uniquement les utilisateurs ayant un investissement actif (nœud, produit AVIP ou vault).
     */
    public function scopeHasActiveInvestments($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('userNodes', function ($sq) {
                $sq->where('active', true)
                   ->where(function ($ssq) {
                       $ssq->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                   });
            })
            ->orWhereHas('userAVIPProducts', function ($sq) {
                $sq->where('active', true)
                   ->where(function ($ssq) {
                       $ssq->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                   });
            })
            ->orWhereHas('vaultInvestments', function ($sq) {
                $sq->where('status', 'active');
            });
        });
    }

    /**
     * Récupère le nœud actif de l'utilisateur sans requêtes complexes dans le contrôleur
     */
    public function activeNode()
    {
        return $this->hasOne(UserNode::class)
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with('node'); // Charge les infos du plan de nœud en même temps
    }

    /**
     * VERSION OPTIMISEE : Recalcul VIP avec Aggregation SQL (1 seule requête pour les stats team)
     */
    public function recalculateVipAndAvipStatus()
    {
        // 1. Investissement Personnel (1 requête)
        $personalInvested = abs($this->transactions()
            ->where('type', 'purchase')
            ->where('amount', '<', 0)
            ->where('status', 'completed')
            ->sum('amount'));

        // 2. Stats d'équipe (1 grosse requête optimisée au lieu de N boucles)
        // On joint les users, on compte les actifs (nœuds, AVIP, coffres), on somme les montants
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
            ->where('referrals.referrer_id', $this->id)
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN user_nodes.id IS NOT NULL OR user_avip_products.id IS NOT NULL OR vault_investments.id IS NOT NULL THEN referrals.id END) as active_referrals,
                COALESCE(SUM(ABS(transactions.amount)), 0) as team_volume
            ')
            ->first();

        $activeReferrals = (int) $teamStats->active_referrals;
        $teamVolume = (float) $teamStats->team_volume;

        // 3. Calcul des niveaux (Logique mise à jour pour VIP 0)
        $newVip = 0;
        if ($personalInvested >= 500000 && $teamVolume >= 5000000 && $activeReferrals >= 10) {
            $newVip = 5;
        } elseif ($personalInvested >= 150000 && $teamVolume >= 1000000 && $activeReferrals >= 5) {
            $newVip = 4;
        } elseif ($personalInvested >= 50000 && $teamVolume >= 200000 && $activeReferrals >= 3) {
            $newVip = 3;
        } elseif ($personalInvested >= 15000 && $teamVolume >= 50000 && $activeReferrals >= 1) {
            $newVip = 2;
        } elseif ($personalInvested >= 15000) {
            $newVip = 1;
        }

        $newAvip = (int) DB::table('user_avip_products')
            ->join('avip_products', 'user_avip_products.avip_product_id', '=', 'avip_products.id')
            ->where('user_avip_products.user_id', $this->id)
            ->where('user_avip_products.active', true)
            ->where(function($q) {
                $q->whereNull('user_avip_products.expires_at')
                  ->orWhere('user_avip_products.expires_at', '>', now());
            })
            ->max('avip_products.avip_level') ?? 0;

        // Mise à jour seulement si nécessaire (optimisation écriture BDD)
        if ($this->vip_level !== $newVip || $this->avip_level !== $newAvip) {
            $this->update([
                'vip_level' => $newVip,
                'avip_level' => $newAvip,
            ]);
        }
    }

    /**
     * Distribute daily referral commissions (L1 = 5%, L2 = 3%, L3 = 1%)
     */
    public function payDailyCommissions($amount)
    {
        $rates = [1 => 0.05, 2 => 0.03, 3 => 0.01];
        $currentSponsor = $this->referrer_id ? User::find($this->referrer_id) : null;
        $level = 1;

        while ($currentSponsor && $level <= 3) {
            $commission = $amount * $rates[$level];
            if ($commission > 0) {
                $currentSponsor->balance += $commission;
                $currentSponsor->save();

                Transaction::create([
                    'user_id' => $currentSponsor->id,
                    'amount' => $commission,
                    'type' => 'commission',
                    'status' => 'completed',
                    'reference' => 'COM-L' . $level . '-' . strtoupper(bin2hex(random_bytes(3))),
                ]);
            }
            $currentSponsor = $currentSponsor->referrer_id ? User::find($currentSponsor->referrer_id) : null;
            $level++;
        }
    }
}