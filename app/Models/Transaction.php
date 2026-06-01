<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    protected $guarded = [];

    /**
     * Conversion automatique des types
     */
    protected $casts = [
        'amount' => 'float', // Convertit automatiquement en nombre décimal
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation : Une transaction appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope : Filtrer uniquement les achats (montants négatifs)
     * Utilisation : Transaction::purchases()->get();
     */
    public function scopePurchases(Builder $query)
    {
        return $query->where('type', 'purchase')->where('amount', '<', 0);
    }
}