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
}